(function() {
	var mode = (typeof checkmateThemeInit !== 'undefined' && checkmateThemeInit.mode) ? checkmateThemeInit.mode : 'dark';
	var mql = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;

	function applyThemeClasses(isDark) {
		document.documentElement.classList.toggle('cm-dark', isDark);
		if (document.body) {
			document.body.classList.toggle('cm-dark', isDark);
		}
	}

	function ensureBodyReady(fn) {
		if (document.body) {
			fn();
			return;
		}
		document.addEventListener('DOMContentLoaded', fn, { once: true });
	}

	if (mode === 'auto') {
		var isDark = !!(mql && mql.matches);
		applyThemeClasses(isDark);
		ensureBodyReady(function() { applyThemeClasses(isDark); });
	} else {
		var isDarkMode = mode === 'dark';
		applyThemeClasses(isDarkMode);
		ensureBodyReady(function() { applyThemeClasses(isDarkMode); });
	}
})();
