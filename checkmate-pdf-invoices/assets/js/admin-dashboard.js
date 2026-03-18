/**
 * Checkmate PDF Invoices - Admin Dashboard JavaScript
 *
 * Handles theme switching and interactive elements.
 *
 * @package Checkmate\PdfInvoices
 */

(function() {
	'use strict';

	// Theme management
	const ThemeManager = {
		mql: window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null,
		currentMode: 'dark',

		init: function() {
			this.currentMode = checkmateAdmin?.themeMode || 'dark';
			this.bindEvents();
			this.applyTheme();
		},

		bindEvents: function() {
			// Theme segment control
			const segment = document.getElementById('cm-theme-segment');
			if (segment) {
				const buttons = segment.querySelectorAll('.cm-segmented-btn');
				buttons.forEach(btn => {
					btn.addEventListener('click', (e) => {
						e.preventDefault();
						const theme = btn.dataset.theme;
						this.setTheme(theme);
					});
				});
			}

			// Listen for system theme changes
			if (this.mql) {
				this.mql.addEventListener('change', () => {
					if (this.currentMode === 'auto') {
						this.applyTheme();
					}
				});
			}
		},

		setTheme: function(mode) {
			this.currentMode = mode;
			this.applyTheme();
			this.updateSegment(mode);
			this.saveTheme(mode);
		},

		applyTheme: function() {
			let isDark = false;

			if (this.currentMode === 'auto') {
				isDark = this.mql && this.mql.matches;
			} else {
				isDark = this.currentMode === 'dark';
			}

			document.documentElement.classList.toggle('cm-dark', isDark);
			document.body.classList.toggle('cm-dark', isDark);
		},

		updateSegment: function(mode) {
			const segment = document.getElementById('cm-theme-segment');
			if (!segment) return;

			segment.dataset.active = mode;

			const buttons = segment.querySelectorAll('.cm-segmented-btn');
			buttons.forEach(btn => {
				const isActive = btn.dataset.theme === mode;
				btn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
			});
		},

		saveTheme: function(mode) {
			if (!checkmateAdmin?.ajaxUrl || !checkmateAdmin?.nonce) return;

			const formData = new FormData();
			formData.append('action', 'checkmate_save_theme_mode');
			formData.append('nonce', checkmateAdmin.nonce);
			formData.append('mode', mode);

			fetch(checkmateAdmin.ajaxUrl, {
				method: 'POST',
				body: formData,
				credentials: 'same-origin'
			}).catch(console.error);
		}
	};

	// Document type cards interaction
	const DocCards = {
		init: function() {
			const cards = document.querySelectorAll('.cm-doc-card');
			cards.forEach(card => {
				card.addEventListener('click', (e) => {
					// Don't trigger if clicking on the manage link
					if (e.target.closest('.cm-doc-link')) return;
					
					const link = card.querySelector('.cm-doc-link');
					if (link) {
						link.click();
					}
				});
			});
		}
	};

	// Template cards interaction
	const TemplateCards = {
		init: function() {
			const useButtons = document.querySelectorAll('.cm-btn-use');
			useButtons.forEach(btn => {
				btn.addEventListener('click', (e) => {
					e.preventDefault();
					const templateId = btn.dataset.template;
					this.useTemplate(templateId);
				});
			});

			const previewButtons = document.querySelectorAll('.cm-btn-preview');
			previewButtons.forEach(btn => {
				btn.addEventListener('click', (e) => {
					e.preventDefault();
					const templateId = btn.dataset.template;
					this.previewTemplate(templateId);
				});
			});
		},

		useTemplate: function(templateId) {
			// TODO: Navigate to template editor with this preset
			console.log('Use template:', templateId);
			// For now, show a coming soon message
			this.showNotice('Template editor coming soon!', 'info');
		},

		previewTemplate: function(templateId) {
			// TODO: Open preview modal
			console.log('Preview template:', templateId);
			this.showNotice('Template preview coming soon!', 'info');
		},

		showNotice: function(message, type) {
			// Simple toast notification
			const existing = document.querySelector('.cm-toast');
			if (existing) existing.remove();

			const toast = document.createElement('div');
			toast.className = 'cm-toast cm-toast-' + type;
			toast.innerHTML = '<span>' + message + '</span>';
			toast.style.cssText = `
				position: fixed;
				bottom: 32px;
				left: 50%;
				transform: translateX(-50%);
				padding: 14px 24px;
				background: rgba(28, 28, 30, 0.95);
				color: #fff;
				border-radius: 12px;
				font-size: 14px;
				font-weight: 500;
				z-index: 10000;
				backdrop-filter: blur(10px);
				-webkit-backdrop-filter: blur(10px);
				box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
				animation: cmToastIn 0.3s ease;
			`;

			// Add animation keyframes if not exists
			if (!document.querySelector('#cm-toast-styles')) {
				const style = document.createElement('style');
				style.id = 'cm-toast-styles';
				style.textContent = `
					@keyframes cmToastIn {
						from { opacity: 0; transform: translateX(-50%) translateY(20px); }
						to { opacity: 1; transform: translateX(-50%) translateY(0); }
					}
					@keyframes cmToastOut {
						from { opacity: 1; transform: translateX(-50%) translateY(0); }
						to { opacity: 0; transform: translateX(-50%) translateY(20px); }
					}
				`;
				document.head.appendChild(style);
			}

			document.body.appendChild(toast);

			setTimeout(() => {
				toast.style.animation = 'cmToastOut 0.3s ease forwards';
				setTimeout(() => toast.remove(), 300);
			}, 3000);
		}
	};

	// Action buttons
	const Actions = {
		init: function() {
			const createBlank = document.getElementById('cm-create-blank');
			if (createBlank) {
				createBlank.addEventListener('click', (e) => {
					e.preventDefault();
					TemplateCards.showNotice('Template editor coming soon!', 'info');
				});
			}

			const createFirst = document.getElementById('cm-create-first');
			if (createFirst) {
				createFirst.addEventListener('click', (e) => {
					e.preventDefault();
					TemplateCards.showNotice('Template editor coming soon!', 'info');
				});
			}
		}
	};

	// Initialize on DOM ready
	document.addEventListener('DOMContentLoaded', function() {
		ThemeManager.init();
		DocCards.init();
		TemplateCards.init();
		Actions.init();
	});

})();
