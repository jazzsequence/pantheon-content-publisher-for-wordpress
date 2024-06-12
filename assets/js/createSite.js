import AddOnApiHelper from "./lib/addonApiHelper";
import axios from "axios";
import {redirectToMainPage} from "./lib/oauthHelper";

export default function createSite() {
	return new Promise(
		async (resolve, reject) => {
			try {
				let url = window.PCCAdmin.site_url;
				let siteId = await AddOnApiHelper.createSite(url);
				let resp = await axios.post(`${window.PCCAdmin.rest_url}/collection`, {
						site_id: siteId,
					}, {headers: {'X-WP-Nonce': window.PCCAdmin.nonce}}
				);
				return resolve();
			} catch (e) {
				reject(e);
			}
		},);
}
