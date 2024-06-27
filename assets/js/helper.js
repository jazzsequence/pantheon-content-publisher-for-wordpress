export function showSpinner() {
	const spinnerBox = document.getElementById('spinner-box');
	const pccContent = document.getElementById('pcc-content');
	if (spinnerBox && pccContent) {
		hideErrorMessage();
		spinnerBox.classList.remove('hidden');
		pccContent.classList.add('hidden');
	}
}

export function hideSpinner() {
	const spinnerBox = document.getElementById('spinner-box');
	const pccContent = document.getElementById('pcc-content');

	if (spinnerBox && pccContent) {
		spinnerBox.classList.add('hidden');
		pccContent.classList.remove('hidden');
	}
}

export function updateSpinnerText(text) {
	const spinnerText = document.getElementById('spinner-text');
	if (spinnerText) {
		spinnerText.textContent = text;
	}
}

/**
 * Get selected post type
 *
 * @returns {string}
 */
export function getSelectedPostType() {
	return document.querySelector('input[name="post_type"]:checked')?.value;
}

/**
 * Show error message
 * @param message
 */
export function showErrorMessage(message) {
	const errorMessageContainer = document.getElementById('pcc-error-message');
	const errorText = document.getElementById('pcc-error-text');
	if (errorMessageContainer && errorText) {
		errorText.textContent = message || 'Error:please try again later';
		errorMessageContainer.classList.remove('hidden');
	}
}

/**
 * Hide error message
 */
export function hideErrorMessage() {
	const errorMessageContainer = document.getElementById('pcc-error-message');
	if (errorMessageContainer){
		errorMessageContainer.classList.add('hidden');
	}
}