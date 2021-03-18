( function () {
	var tooltips = document.querySelectorAll('.mw-parser-output .simple-terms-tooltip'),
		wrapId = "simple-terms-wrap",
		dataAttribName = 'data-title',
		timeoutId,
		allowHtml = mw.config.get('wgSimpleTermsAllowHtml');

	if (tooltips === null) {
		return;
	}

	if (!document.getElementById(wrapId)) {
		var simpleTermsWrap = document.createElement("div");
		simpleTermsWrap.id = wrapId;
		document.body.appendChild(simpleTermsWrap);

		simpleTermsWrap.addEventListener('mouseover', function () {
			clearTimeout(timeoutId);
		});
		simpleTermsWrap.addEventListener('mouseout', hideToolTip);
	}

	tooltips.forEach(function (tooltip) {
		var initialized = tooltip.getAttribute(dataAttribName);

		if (!initialized || initialized.length <= 0) {
			var data = tooltip.getAttribute('title');
			tooltip.removeAttribute('title');
			tooltip.setAttribute(dataAttribName, data);
		}

		tooltip.addEventListener('mouseover', showToolTip);
		tooltip.addEventListener('click', showToolTip);
		tooltip.addEventListener('mouseout', hideToolTip);
	})

	function showToolTip(e) {
		clearTimeout(timeoutId);
		var wrap = document.getElementById(wrapId),
			ex = e.clientX,
			ey = e.clientY;

		if (allowHtml === true) {
			wrap.innerHTML = this.getAttribute(dataAttribName);
		} else {
			wrap.innerText = this.getAttribute(dataAttribName);
		}

		wrap.style.top = ey + 10 + 'px';

		if ((wrap.offsetWidth + ex) < window.innerWidth) {
			wrap.style.left = ex + 10 + 'px';
		} else {
			wrap.style.left = window.innerWidth - wrap.offsetWidth + 'px';
		}

		wrap.classList.add('visible');
	}

	function hideToolTip() {
		timeoutId = setTimeout(function () {
			document.getElementById(wrapId).classList.remove('visible');
		}, 250)
	}

	document.body.addEventListener('click', function (event) {
		if (typeof event.target.id !== 'undefined' && event.target.id === wrapId) {
			return;
		}

		hideToolTip();
	})
}() );
