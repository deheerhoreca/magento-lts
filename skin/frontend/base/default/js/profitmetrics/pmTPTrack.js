function _pm_geturlparm( parmname ) {
	var regex = new RegExp( "[\\?&]" + parmname + "=([^&#]*)" );
	var result = regex.exec( location.search );
	var ret = result === null ? "" : decodeURIComponent( result[1].replace( /\+/g, " " ) );

	return ret;
}

function _pm_getGclid() {
	let gclid = _pm_geturlparm("gclid");

	if (gclid != "") {
		return gclid;
	}
	let gclidfromGclAw = _pm_getcookie('_gcl_aw');
	if (gclidfromGclAw != null) {
		let gclAwSplitAll = gclidfromGclAw.split('.');
		if (gclAwSplitAll.length >= 3) {
			return gclidfromGclAw.substring( gclAwSplitAll[0].length + gclAwSplitAll[1].length + 1 + 1 ); // each +1 corresponds to '.'s
		}
	}
	let gclidfromFPGCLAW = _pm_getcookie('FPGCLAW');
	if (gclidfromFPGCLAW != null) {
		const fpgSplitAll = gclidfromFPGCLAW.split('.');
		if (fpgSplitAll.length >= 3) {
			return gclidfromFPGCLAW.substring( fpgSplitAll[0].length + fpgSplitAll[1].length + 1 + 1 ); // each +1 corresponds to '.'s
		}
	}
}

function _pm_getcookie( cookiename ) {
	cookiename += "=";
	if( document.cookie.indexOf( cookiename ) != -1 ) {
		var idxofSource = document.cookie.indexOf( cookiename ) + cookiename.length;
		var idxofEnd = document.cookie.indexOf( ";", idxofSource );
		var cookval = "";
		if( idxofEnd == -1 ) {
			cookval = document.cookie.substr( idxofSource );
		} else {
			cookval = document.cookie.substr( idxofSource, idxofEnd-idxofSource );
		}
		if( cookval.length != 0 ) {
			return cookval;
		} else {
			return null;
		}
	}
}

function _pm_getGa4SessionId() {
	const gaCookies = document.cookie.split(';')
	.map(cookie => cookie.trim())
	.filter(cookie => cookie.startsWith('_ga_'));

	const result = gaCookies.map(cookieString => {
		const eqIndex = cookieString.indexOf('=');
		if (eqIndex === -1) return null;

		const cookieName = cookieString.substring(0, eqIndex);
		const cookieValue = cookieString.substring(eqIndex + 1);
		const measurementId = cookieName.substring(4);
		let sessionId = null;

		if (cookieValue.startsWith('GS2.')) {
			const parts = cookieValue.split('.');
			if (parts.length >= 3) {
				const dataSegments = parts[2].split('$');
				const sessionSegment = dataSegments.find(segment => segment.startsWith('s'));
				if (sessionSegment) {
					sessionId = sessionSegment.substring(1);
				}
			}
		} else if (cookieValue.startsWith('GS1.')) {
			const parts = cookieValue.split('.');
			if (parts.length >= 3) {
				sessionId = parts[2];
			}
		}
		return sessionId ? `${measurementId}:${sessionId}` : null;
	}).filter(Boolean);

	const retImploded = result.join(',');
	return retImploded && retImploded.length > 0 ? retImploded : null;
}

function _pm_getGa4SessionCount() {
	const gaCookies = document.cookie.split(';')
	.map(cookie => cookie.trim())
	.filter(cookie => cookie.startsWith('_ga_'));

	const result = gaCookies.map(cookieString => {
		const eqIndex = cookieString.indexOf('=');
		if (eqIndex === -1) return null;

		const cookieName = cookieString.substring(0, eqIndex);
		const cookieValue = cookieString.substring(eqIndex + 1);
		const measurementId = cookieName.substring(4);
		let sessionCount = null;

		if (cookieValue.startsWith('GS2.')) {
			const parts = cookieValue.split('.');
			if (parts.length >= 3) {
				const dataSegments = parts[2].split('$');
				const sessionSegment = dataSegments.find(segment => segment.startsWith('o'));
				if (sessionSegment) {
					sessionCount = sessionSegment.substring(1);
				}
			}
		} else if (cookieValue.startsWith('GS1.')) {
			const parts = cookieValue.split('.');
			if (parts.length >= 4) {
				sessionCount = parts[3];
			}
		}
		return sessionCount ? `${measurementId}:${sessionCount}` : null;
	}).filter(Boolean);

	const retImploded = result.join(',');
	return retImploded && retImploded.length > 0 ? retImploded : null;
}

function _pm_getStoredTPTrack() {
	var ret = _pm_getcookie( "pmTPTrack" );
	if( null != ret && ret.length > 0 ) {
		ret = JSON.parse( decodeURIComponent( ret ) );
	} else {
		ret = { gclid: null, gacid: null, gacid_source: null, fbp: null, fbc: null, gbraid: null, wbraid: null, sccid: null, ttclid: null, msclkid: null, twclid: null, ga4SessionId: null, ga4SessionCount: null, timestamp: (((new Date)/1E3|0) - 100) };
	}

	return ret;
}

function _pm_storeTPTrack( tptrack ) {
	var _pm_old_tpTrackCookVal = _pm_getcookie( "pmTPTrack" );

	var _pm_tpTrackCookVal = encodeURIComponent( JSON.stringify( tptrack ) );
	document.cookie = "pmTPTrack=" + _pm_tpTrackCookVal + "; path=/";
}



function _pm_GetGacidFromTracker() {
	if( typeof ga == 'function' ) {
		try {
			ga(function(tracker) {
				var gacid = tracker.get( 'clientId' );
				if( null != gacid ) {

					var _pm_curPMTPTrack = _pm_getStoredTPTrack();
					if( _pm_curPMTPTrack.gacid != gacid ) {
						_pm_curPMTPTrack.gacid = gacid;
						_pm_curPMTPTrack.gacid_source = "gatracker";
						_pm_curPMTPTrack.timestamp = (new Date)/1E3|0;

						_pm_storeTPTrack( _pm_curPMTPTrack );
					}
				}
			});
		} catch( eee ) {
		}
	} else {
		setTimeout( _pm_GetGacidFromTracker, 100 );
	}
}

function load_pmTPTrack() {
	var _pm_curPMTPTrack = _pm_getStoredTPTrack();

	var _pm_newFBC = _pm_getcookie( "_fbc" );
	if( null != _pm_newFBC && _pm_curPMTPTrack.fbc != _pm_newFBC ) {
		_pm_curPMTPTrack.fbc = _pm_newFBC;
		_pm_curPMTPTrack.timestamp = (new Date)/1E3|0;
	}

	var _pm_newFBP = _pm_getcookie( "_fbp" );
	if( null != _pm_newFBP && _pm_curPMTPTrack.fbp != _pm_newFBP ) {
		_pm_curPMTPTrack.fbp = _pm_newFBP;
		_pm_curPMTPTrack.timestamp = (new Date)/1E3|0;
	}

	var _pm_newGacid = _pm_getcookie( "_ga" );
	if( null != _pm_newGacid && _pm_curPMTPTrack.gacid_source != "gatracker" && _pm_curPMTPTrack.gacid != _pm_newGacid ) {
		_pm_curPMTPTrack.gacid = _pm_newGacid;
		_pm_curPMTPTrack.gacid_source = "gacookie";
		_pm_curPMTPTrack.timestamp = (new Date)/1E3|0;
	}

	var _pm_newGclid = _pm_getGclid();
	if( _pm_newGclid != "" ) {
		_pm_curPMTPTrack.gclid = _pm_newGclid;
		_pm_curPMTPTrack.timestamp = (new Date)/1E3|0;
	}

	var _pm_gbraid = _pm_geturlparm( "gbraid" );
	if( _pm_gbraid != "" ) {
		_pm_curPMTPTrack.gbraid = _pm_gbraid;
		_pm_curPMTPTrack.timestamp = (new Date)/1E3|0;
	}

	var _pm_wbraid = _pm_geturlparm( "wbraid" );
	if( _pm_wbraid != "" ) {
		_pm_curPMTPTrack.wbraid = _pm_wbraid;
		_pm_curPMTPTrack.timestamp = (new Date)/1E3|0;
	}

	var _pm_sccid = _pm_geturlparm( "sccid" );
	if( _pm_sccid != "" ) {
		_pm_curPMTPTrack.sccid = _pm_sccid;
		_pm_curPMTPTrack.timestamp = (new Date)/1E3|0;
	}

	var _pm_ttclid = _pm_geturlparm( "ttclid" );
	if( _pm_ttclid != "" ) {
		_pm_curPMTPTrack.ttclid = _pm_ttclid;
		_pm_curPMTPTrack.timestamp = (new Date)/1E3|0;
	}

	var _pm_msclkid = _pm_geturlparm( "msclkid" );
	if( _pm_msclkid != "" ) {
		_pm_curPMTPTrack.msclkid = _pm_msclkid;
		_pm_curPMTPTrack.timestamp = (new Date)/1E3|0;
	}

	var _pm_twclid = _pm_geturlparm( "twclid" );
	if( _pm_twclid != "" ) {
		_pm_curPMTPTrack.twclid = _pm_twclid;
		_pm_curPMTPTrack.timestamp = (new Date)/1E3|0;
	}

	var _pm_ga4SessionId = _pm_getGa4SessionId();
	if( _pm_ga4SessionId != null && _pm_ga4SessionId != "" ) {
		_pm_curPMTPTrack.ga4SessionId = _pm_ga4SessionId;
		_pm_curPMTPTrack.timestamp = (new Date)/1E3|0;
	}

	var _pm_ga4SessionCount = _pm_getGa4SessionCount();
	if( _pm_ga4SessionCount != null && _pm_ga4SessionCount != "" ) {
		_pm_curPMTPTrack.ga4SessionCount = _pm_ga4SessionCount;
		_pm_curPMTPTrack.timestamp = (new Date)/1E3|0;
	}
	//Set cc_marketing and cc_statistics according to consent and blockScriptBeforeConsent
	_pm_curPMTPTrack.cc_marketing = cc_marketing;
	_pm_curPMTPTrack.cc_statistics = cc_statistics;

	_pm_storeTPTrack(_pm_curPMTPTrack);
	_pm_GetGacidFromTracker();

	// Set previousDecision
	localStorage.setItem('pfm-consent-granted', true);
}

// Set default consent state
let cc_statistics = false;
let cc_marketing = false;


function init() {
	let previousDecision = localStorage.getItem('pfm-consent-granted');

	if (!window.blockScriptBeforeConsent || previousDecision) {
		// Set default consent to truw
		cc_statistics = true;
		cc_marketing = true;
		load_pmTPTrack()
	}

	// CookieBot
	// Get consent
	if (typeof Cookiebot !== 'undefined' && (Cookiebot?.consent?.statistics && Cookiebot?.consent?.marketing)) {
		cc_statistics = Cookiebot.consent.statistics;
		cc_marketing = Cookiebot.consent.marketing;
		load_pmTPTrack();

	} else {
		// Add event listener
		window.addEventListener('CookiebotOnConsentReady', function() {
			if (Cookiebot?.consent?.statistics && Cookiebot?.consent?.marketing) {
				cc_statistics = Cookiebot.consent.statistics;
				cc_marketing = Cookiebot.consent.marketing;
				load_pmTPTrack();
			}
		});
	}

	// CookieInformation
	// Get consent
	if (typeof CookieInformation !== 'undefined' && (CookieInformation?.getConsentGivenFor('cookie_cat_statistic') && CookieInformation?.getConsentGivenFor('cookie_cat_marketing'))) {
		cc_statistics = CookieInformation.getConsentGivenFor('cookie_cat_statistic');
		cc_marketing = CookieInformation.getConsentGivenFor('cookie_cat_marketing');
		load_pmTPTrack();
	} else {
		// Add event listener
		window.addEventListener('CookieInformationConsentGiven', function() {
			if (CookieInformation?.getConsentGivenFor('cookie_cat_statistic') && CookieInformation?.getConsentGivenFor('cookie_cat_marketing')) {
				cc_statistics = CookieInformation.getConsentGivenFor('cookie_cat_statistic');
				cc_marketing = CookieInformation.getConsentGivenFor('cookie_cat_marketing');
				load_pmTPTrack();
			}
		});
	}

	// OneTrust
	// Get consent
	if (typeof OneTrust !== 'undefined' && (OnetrustActiveGroups?.includes("2") && OnetrustActiveGroups?.includes("4"))) {
		cc_statistics = OnetrustActiveGroups.includes("2");
		cc_marketing = OnetrustActiveGroups.includes("4");
		load_pmTPTrack();
	} else {
		// Add event listener
		window.addEventListener("OneTrustGroupsUpdated", event => {
			if (event?.detail?.some(group => group.includes("4")) && event?.detail?.some(group => group.includes("2"))) {
				cc_statistics = event.detail.some(group => group.includes("4"));
				cc_marketing = event.detail.some(group => group.includes("2"));
				load_pmTPTrack();
			}
		});
	}

	// CookieYes
	// Get consent
	if (typeof getCkyConsent !== 'undefined' && (getCkyConsent()?.categories?.analytics && getCkyConsent()?.categories?.advertisement)) {
		cc_statistics = getCkyConsent().categories.analytics;
		cc_marketing = getCkyConsent().categories.advertisement;
		load_pmTPTrack();
	} else {
		// Add event listener
		document.addEventListener("cookieyes_consent_update", function(eventData) {
			if (eventData?.detail?.accepted?.includes("analytics") && eventData?.detail?.accepted?.includes("advertisement")) {
				cc_statistics = eventData.detail.accepted.includes("analytics");
				cc_marketing = eventData.detail.accepted.includes("advertisement");
				load_pmTPTrack();
			}
		});
	}

	// CookieFirst
	// Get consent
	if (typeof CookieFirst !== 'undefined' && (CookieFirst?.consent?.performance && CookieFirst?.consent?.advertising)) {
		cc_statistics = CookieFirst.consent.performance;
		cc_marketing = CookieFirst.consent.advertising;
		load_pmTPTrack();
	} else {
		// Add event listener
		window.addEventListener('cf_consent', function(event) {
			if (event?.detail?.performance && event?.detail?.advertising) {
				cc_statistics = event.detail.performance;
				cc_marketing = event.detail.advertising;
				load_pmTPTrack();
			}
		});
		// Add event listener
		window.addEventListener('cf_consent_loaded', function(event) {
			if (event?.detail?.performance && event?.detail?.advertising) {
				cc_statistics = event.detail.performance;
				cc_marketing = event.detail.advertising;
				load_pmTPTrack();
			}
		});
	}

	// CookieScript
	// Get consent
	if (typeof CookieScript !== 'undefined' && (CookieScript?.instance?.currentState()?.categories?.includes("performance") && CookieScript?.instance?.currentState()?.categories?.includes("targeting"))) {
		cc_statistics = CookieScript.instance.currentState().categories.includes("performance");
		cc_marketing = CookieScript.instance.currentState().categories.includes("targeting");
		load_pmTPTrack();
	} else {
		// Add event listener
		window.addEventListener('CookieScriptCategory-strict', function() {
			if (CookieScript?.instance?.currentState()?.categories?.includes("performance") && CookieScript?.instance?.currentState()?.categories?.includes("targeting")) {
				cc_statistics = CookieScript.instance.currentState().categories.includes("performance");
				cc_marketing = CookieScript.instance.currentState().categories.includes("targeting");
				load_pmTPTrack();
			}
		});
	}

	// Google Consent Mode
	// Get consent
	if (typeof window.google_tag_data !== 'undefined' && window.google_tag_data?.ics && (window.google_tag_data?.ics?.entries?.ad_storage?.update && window.google_tag_data?.ics?.entries?.analytics_storage?.update)) {
		cc_marketing = window.google_tag_data.ics.entries.ad_storage.update;
		cc_statistics = window.google_tag_data.ics.entries.analytics_storage.update;
		load_pmTPTrack();
	} else {
		// Add event listener
		window.google_tag_data?.ics?.addListener(["ad_storage", "analytics_storage"], function(event) {
			if (window.google_tag_data?.ics?.entries?.ad_storage?.update && window.google_tag_data?.ics?.entries?.analytics_storage?.update) {
				cc_marketing = window.google_tag_data.ics.entries.ad_storage.update;
				cc_statistics = window.google_tag_data.ics.entries.analytics_storage.update;
				load_pmTPTrack();
			}
		});
	}
}
init();
