function _pm_geturlparm( parmname ) {
	var regex = new RegExp( "[\\?&]" + parmname + "=([^&#]*)" );
	var result = regex.exec( location.search );
	var ret = result === null ? "" : decodeURIComponent( result[1].replace( /\+/g, " " ) );

	return ret;
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
	const retImploded = document.cookie.split(';')
		.filter((c) => c.indexOf('_ga_') !== -1)
		.map((c) => c.trim().split('.'))
		.map((c) => c[0].substring(4, c[0].indexOf('=')) + ":" + c[2]) // index 2 for session id
		.join(',')
	;

	return null != retImploded && retImploded.length > 0 ? retImploded : null;
}

function _pm_getGa4SessionCount() {
	const retImploded = document.cookie.split(';')
		.filter((c) => c.indexOf('_ga_') !== -1)
		.map((c) => c.trim().split('.'))
		.map((c) => c[0].substring(4, c[0].indexOf('=')) + ":" + c[3]) // index 3 for session count
		.join(',')
	;

	return null != retImploded && retImploded.length > 0 ? retImploded : null;
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

	var _pm_newGclid = _pm_geturlparm( "gclid" );
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

	_pm_storeTPTrack(_pm_curPMTPTrack);
	_pm_GetGacidFromTracker();
}

var load_pmTPTrackOnce = (function() {
	var executed = false;
	return function() {
		if (!executed) {
			load_pmTPTrack();
			executed = true;
			localStorage.setItem('pfm-consent-granted', true);
		}
	};
})();

function init() {
	let previousDecision = localStorage.getItem('pfm-consent-granted');
	if (!window.blockScriptBeforeConsent || previousDecision) {
		load_pmTPTrackOnce();
	} else {

		if (typeof Cookiebot !== 'undefined') {
			window.addEventListener('CookiebotOnAccept', function() {
				if (Cookiebot.consent.marketing && Cookiebot.consent.statistics) {
					load_pmTPTrackOnce();
				}
			});
		}

		if (typeof CookieInformation !== 'undefined') {
			window.addEventListener('CookieInformationConsentGiven', function() {
				if (CookieInformation.getConsentGivenFor('cookie_cat_marketing') &&
					CookieInformation.getConsentGivenFor('cookie_cat_statistic')) {
					load_pmTPTrackOnce();
				}
			});
		}

		if (typeof cookieyes !== 'undefined') {
			document.addEventListener("cookieyes_consent_update", function(eventData) {
				const data = eventData.detail;
				if (data.accepted.includes("advertisement") && data.accepted.includes("analytics")) {
					load_pmTPTrackOnce();
				}
			});
		}

		if (typeof _iub !== 'undefined' && _iub.csConfiguration) {
			_iub.csConfiguration.callback = {
				onConsentRead: function() {
					const purposes = _iub.cs.api.getPreferences().purposes;
					// consent givent for measurement (4) and marketing (5)
					if (purposes[4] && purposes[5]) {
						load_pmTPTrackOnce();
					}
				}
			};
		}

		if (typeof window.google_tag_data !== 'undefined' && window.google_tag_data.ics) {
			window.google_tag_data.ics.addListener(
				["ad_storage", "analytics_storage"],
				function(event) {
					load_pmTPTrackOnce();
				}
			);
		}
	}
}
init();