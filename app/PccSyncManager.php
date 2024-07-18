<?php

namespace PCC;

use PccPhpSdk\api\Query\Enums\PublishingLevel;
use PccPhpSdk\api\Response\Article;
use PccPhpSdk\api\ArticlesApi;
use PccPhpSdk\core\PccClient;
use PccPhpSdk\core\PccClientConfig;

class PccSyncManager
{
	/**
	 * @var string $siteId
	 */
	private string $siteId;
	private string $accessToken;

	public function __construct()
	{
		$this->siteId = get_option(PCC_SITE_ID_OPTION_KEY);
		$this->accessToken = get_option(PCC_ACCESS_TOKEN_OPTION_KEY);
	}

	/**
	 * Get PccClient instance.
	 *
	 * @return PccClient
	 */
	public function pccClient(string $pccGrant = null): PccClient
	{
		$args = [$this->siteId, $this->accessToken];
		if ($pccGrant) {
			$args = [$this->siteId, '', null, $pccGrant];
		}

		return new PccClient(new PccClientConfig(...$args));
	}

	/**
	 * Fetch and store document.
	 *
	 * @param $documentId
	 * @param bool $isDraft
	 * @return int
	 */
	public function fetchAndStoreDocument($documentId, $isDraft = false)
	{
		$articlesApi = new ArticlesApi($this->pccClient());
		$article = $articlesApi->getArticleById($documentId);

		return $this->storeArticle($article, $isDraft);
	}

	/**
	 * Store articles from PCC to WordPress.
	 */
	public function storeArticles()
	{
		if (!$this->getIntegrationPostType()) {
			return;
		}
		$articlesApi = new \PccPhpSdk\api\ArticlesApi($this->pccClient());
		$articles = $articlesApi->getAllArticles();
		/** @var Article $article */
		foreach ($articles->articles as $article) {
			$this->storeArticle($article);
		}
	}

	/**
	 * Store article.
	 *
	 * @param Article $article
	 * @param bool $isDraft
	 * @return int
	 */
	private function storeArticle(Article $article, bool $isDraft = false)
	{
		$postId = $this->findExistingConnectedPost($article->id);

		return $this->createOrUpdatePost($postId, $article, $isDraft);
	}

	/**
	 * Create or update post.
	 *
	 * @param $postId
	 * @param Article $article
	 * @return int post id
	 */
	private function createOrUpdatePost($postId, Article $article, bool $isDraft = false)
	{
		$data = [
			'post_title' => $article->title,
			'post_content' => $article->content,
			'post_status' => $isDraft ? 'draft' : 'publish',
			'post_name' => $article->slug,
			'post_type' => $this->getIntegrationPostType(),
		];
		if (!$postId) {
			$data['ID'] = $postId;
			$postId = wp_insert_post($data);
			update_post_meta($postId, PCC_CONTENT_META_KEY, $article->id);
			return $postId;
		}

		$data['ID'] = $postId;
		wp_update_post($data);
		return $postId;
	}

	/**
	 * @param $value
	 * @return int|null
	 */
	public function findExistingConnectedPost($value)
	{
		global $wpdb;

		$post_id = $wpdb->get_var($wpdb->prepare(
			"SELECT post_id FROM $wpdb->postmeta WHERE meta_key = %s AND meta_value = %s LIMIT 1",
			PCC_CONTENT_META_KEY,
			$value
		));

		return $post_id ? (int)$post_id : null;
	}

	/**
	 * Get selected integration post type.
	 *
	 * @return false|mixed|null
	 */
	private function getIntegrationPostType()
	{
		return get_option(PCC_INTEGRATION_POST_TYPE_OPTION_KEY);
	}

	/**
	 * Publish post by document id.
	 *
	 * @param $documentId
	 * @return void
	 */
	public function unPublishPostByDocumentId($documentId)
	{
		$postId = $this->findExistingConnectedPost($documentId);
		if (!$postId) {
			return;
		}

		wp_update_post([
			'ID' => $postId,
			'post_status' => 'draft',
		]);
	}

	public function preaprePreviewingURL(string $documentId, $postId = null)
	{
		$postId = $postId ?: $this->findExistingConnectedPost($documentId);
		return add_query_arg(
			[
				'preview' => 'google_document',
				'publishing_level' => PublishingLevel::REALTIME->value,
				'document_id' => $documentId,
			],
			get_permalink($postId)
		);
	}

	/**
	 * Get preview content from PCC.
	 *
	 * @param string $documentId
	 * @param string $pccGrant
	 * @return Article
	 */
	public function getPreviewContent(string $documentId, string $pccGrant)
	{
		$articleApi = new ArticlesApi($this->pccClient($pccGrant));

		return $articleApi->getArticleById($documentId, [], PublishingLevel::REALTIME);
	}
}