(function() {
	var ajaxUrl = checkmateTemplates.ajaxUrl;
	var nonce = checkmateTemplates.nonce;
	var editorUrl = checkmateTemplates.editorUrl;
	var autoOpenModal = checkmateTemplates.autoOpenModal;
	var noTemplatesText = checkmateTemplates.i18n.noTemplates;

	// Track which modal to return to after preview closes
	var previewReturnModal = null;

	// Template type filter navigation (no page reload)
	document.querySelectorAll('.cm-nav .cm-nav-item[data-filter]').forEach(function(btn) {
		btn.addEventListener('click', function() {
			// Update active state
			document.querySelectorAll('.cm-nav .cm-nav-item').forEach(function(b) { b.classList.remove('active'); });
			this.classList.add('active');

			var filter = this.dataset.filter;
			var rows = document.querySelectorAll('.cm-table tbody tr[data-type]');
			var visibleCount = 0;

			rows.forEach(function(row) {
				var type = row.dataset.type;
				var show = filter === 'all' || type === filter;
				row.style.display = show ? '' : 'none';
				if (show) visibleCount++;
			});

			// Show/hide empty state for filtered view
			var tableWrap = document.querySelector('.cm-table-wrap');
			var emptyFilter = document.querySelector('.cm-empty-filter');

			if (visibleCount === 0 && rows.length > 0) {
				if (tableWrap) tableWrap.style.display = 'none';
				if (!emptyFilter) {
					emptyFilter = document.createElement('div');
					emptyFilter.className = 'cm-empty-filter';
					emptyFilter.innerHTML = '<div class="cm-empty-icon"><span class="dashicons dashicons-filter"></span></div>' +
						'<p class="cm-empty-desc">' + noTemplatesText + '</p>';
					if (tableWrap && tableWrap.parentNode) {
						tableWrap.parentNode.insertBefore(emptyFilter, tableWrap.nextSibling);
					}
				}
				emptyFilter.style.display = '';
			} else {
				if (tableWrap) tableWrap.style.display = '';
				if (emptyFilter) emptyFilter.style.display = 'none';
			}

			// Update URL without reload
			if (window.history && window.history.replaceState) {
				var url = new URL(window.location.href);
				if (filter === 'all') {
					url.searchParams.delete('type');
				} else {
					url.searchParams.set('type', filter);
				}
				window.history.replaceState({}, '', url.toString());
			}
		});
	});

	// Modal helpers
	function openModal(id) {
		var modal = document.getElementById(id);
		if (modal) {
			modal.classList.add('is-open');
			modal.setAttribute('aria-hidden', 'false');
			document.body.classList.add('cm-modal-open');
		}
	}

	function closeModal(id) {
		var modal = document.getElementById(id);
		if (modal) {
			modal.classList.remove('is-open');
			modal.setAttribute('aria-hidden', 'true');
		}
		// Check if any modals are still open
		if (!document.querySelector('.cm-modal.is-open')) {
			document.body.classList.remove('cm-modal-open');
		}
	}

	function closeAllModals() {
		document.querySelectorAll('.cm-modal.is-open').forEach(function(m) {
			m.classList.remove('is-open');
			m.setAttribute('aria-hidden', 'true');
		});
		document.body.classList.remove('cm-modal-open');
		previewReturnModal = null;
	}

	// Close preview modal and optionally return to previous modal
	function closePreviewModal() {
		closeModal('cm-preview-modal');
		if (previewReturnModal) {
			openModal(previewReturnModal);
			previewReturnModal = null;
		}
	}

	// Close modal on backdrop/button click (for regular modals)
	document.querySelectorAll('[data-cm-modal-close]').forEach(function(el) {
		el.addEventListener('click', function(e) {
			e.preventDefault();
			closeAllModals();
		});
	});

	// Close preview modal with return logic
	document.querySelectorAll('[data-cm-preview-close]').forEach(function(el) {
		el.addEventListener('click', function(e) {
			e.preventDefault();
			closePreviewModal();
		});
	});

	// Close on Escape
	document.addEventListener('keydown', function(e) {
		if (e.key === 'Escape') {
			var previewModal = document.getElementById('cm-preview-modal');
			if (previewModal && previewModal.classList.contains('is-open')) {
				closePreviewModal();
			} else {
				closeAllModals();
			}
		}
	});

	// Create Template buttons -> open preset modal
	document.querySelectorAll('#cm-create-new, #cm-create-first').forEach(function(btn) {
		btn.addEventListener('click', function() { openModal('cm-preset-modal'); });
	});

	// Preset filter
	document.querySelectorAll('.cm-preset-filter').forEach(function(btn) {
		btn.addEventListener('click', function() {
			document.querySelectorAll('.cm-preset-filter').forEach(function(b) { b.classList.remove('active'); });
			this.classList.add('active');
			var filter = this.dataset.filter;
			document.querySelectorAll('.cm-preset-card').forEach(function(card) {
				var type = card.dataset.type;
				card.style.display = (filter === 'all' || type === filter || type === 'all') ? '' : 'none';
			});
		});
	});

	// Preset card click -> use template
	document.querySelectorAll('.cm-preset-card').forEach(function(card) {
		card.addEventListener('click', function(e) {
			if (e.target.closest('.cm-preset-preview')) return;
			var preset = this.dataset.preset;
			if (preset === 'blank') {
				window.location.href = editorUrl;
			} else {
				window.location.href = editorUrl + '&preset=' + encodeURIComponent(preset);
			}
		});

		// Use button
		var useBtn = card.querySelector('.cm-preset-use');
		if (useBtn) {
			useBtn.addEventListener('click', function(e) {
				e.stopPropagation();
				var preset = card.dataset.preset;
				if (preset === 'blank') {
					window.location.href = editorUrl;
				} else {
					window.location.href = editorUrl + '&preset=' + encodeURIComponent(preset);
				}
			});
		}

		// Preview button (from preset card - remember to return to preset modal)
		var previewBtn = card.querySelector('.cm-preset-preview');
		if (previewBtn) {
			previewBtn.addEventListener('click', function(e) {
				e.stopPropagation();
				var preset = card.dataset.preset;
				if (preset && preset !== 'blank') {
					previewReturnModal = 'cm-preset-modal';
					closeModal('cm-preset-modal');
					showPreview({ preset: preset });
				}
			});
		}
	});

	// Preview modal
	var previewIframe = document.getElementById('cm-preview-iframe');
	var previewLoading = document.getElementById('cm-preview-loading');

	function showPreview(params) {
		openModal('cm-preview-modal');
		if (previewLoading) previewLoading.style.display = 'flex';
		if (previewIframe) previewIframe.style.opacity = '0';

		var urlParams = new URLSearchParams(Object.assign({ action: 'checkmate_preview_template', nonce: nonce }, params));

		fetch(ajaxUrl, {
			method: 'POST',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: urlParams
		})
		.then(function(response) { return response.text(); })
		.then(function(html) {
			if (previewIframe) {
				var doc = previewIframe.contentDocument || previewIframe.contentWindow.document;
				doc.open();
				doc.write(html);
				doc.close();
				previewIframe.style.opacity = '1';
			}
		})
		.catch(function(err) {
			console.error('Preview error:', err);
		})
		.finally(function() {
			if (previewLoading) previewLoading.style.display = 'none';
		});
	}

	// Table row actions - Preview (no return modal needed)
	document.querySelectorAll('.cm-action-preview').forEach(function(btn) {
		btn.addEventListener('click', function() {
			var templateId = this.dataset.templateId;
			previewReturnModal = null;
			showPreview({ template_id: templateId });
		});
	});

	// Duplicate
	document.querySelectorAll('.cm-action-duplicate').forEach(function(btn) {
		btn.addEventListener('click', function() {
			var templateId = this.dataset.templateId;
			this.disabled = true;
			var self = this;
			fetch(ajaxUrl, {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: new URLSearchParams({
					action: 'checkmate_duplicate_template',
					nonce: nonce,
					template_id: templateId
				})
			})
			.then(function(response) { return response.json(); })
			.then(function(result) {
				if (result.success) {
					window.location.reload();
				} else {
					alert(result.data && result.data.message ? result.data.message : 'Failed to duplicate template');
				}
			})
			.catch(function(err) {
				console.error('Duplicate error:', err);
			})
			.finally(function() {
				self.disabled = false;
			});
		});
	});

	// Delete
	var deleteTemplateId = null;
	document.querySelectorAll('.cm-action-delete').forEach(function(btn) {
		btn.addEventListener('click', function() {
			deleteTemplateId = this.dataset.templateId;
			openModal('cm-delete-modal');
		});
	});

	var confirmDeleteBtn = document.getElementById('cm-confirm-delete');
	if (confirmDeleteBtn) {
		confirmDeleteBtn.addEventListener('click', function() {
			if (!deleteTemplateId) return;
			this.disabled = true;
			var self = this;
			fetch(ajaxUrl, {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: new URLSearchParams({
					action: 'checkmate_delete_template',
					nonce: nonce,
					template_id: deleteTemplateId
				})
			})
			.then(function(response) { return response.json(); })
			.then(function(result) {
				if (result.success) {
					var row = document.querySelector('tr[data-template-id="' + deleteTemplateId + '"]');
					if (row) row.remove();
					closeModal('cm-delete-modal');
					var tbody = document.querySelector('.cm-table tbody');
					if (tbody && !tbody.querySelector('tr')) {
						window.location.reload();
					}
				} else {
					alert(result.data && result.data.message ? result.data.message : 'Failed to delete template');
				}
			})
			.catch(function(err) {
				console.error('Delete error:', err);
			})
			.finally(function() {
				self.disabled = false;
				deleteTemplateId = null;
			});
		});
	}

	// Status toggle (activate/deactivate) - using toggle switch
	document.querySelectorAll('.cm-toggle').forEach(function(toggle) {
		var input = toggle.querySelector('.cm-toggle-input');
		if (!input) return;

		input.addEventListener('change', function() {
			var templateId = toggle.dataset.templateId;
			var isActive = this.checked;
			this.disabled = true;
			var self = this;
			fetch(ajaxUrl, {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: new URLSearchParams({
					action: 'checkmate_toggle_template_status',
					nonce: nonce,
					template_id: templateId,
					is_active: isActive ? '1' : '0'
				})
			})
			.then(function(response) { return response.json(); })
			.then(function(result) {
				if (!result.success) {
					self.checked = !isActive;
				}
			})
			.catch(function(err) {
				console.error('Status toggle error:', err);
				self.checked = !isActive;
			})
			.finally(function() {
				self.disabled = false;
			});
		});
	});

	// Event assignment change
	document.querySelectorAll('.cm-event-select').forEach(function(select) {
		select.addEventListener('change', function() {
			var templateId = this.dataset.templateId;
			var event = this.value;
			fetch(ajaxUrl, {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: new URLSearchParams({
					action: 'checkmate_assign_template_event',
					nonce: nonce,
					template_id: templateId,
					event: event
				})
			})
			.catch(function(err) {
				console.error('Event assignment error:', err);
			});
		});
	});

	// Auto-open preset browser modal if action=create is in URL
	if (autoOpenModal) {
		openModal('cm-preset-modal');
		if (window.history && window.history.replaceState) {
			var cleanUrl = window.location.href.replace(/[?&]action=create/, '').replace(/\?&/, '?').replace(/\?$/, '');
			window.history.replaceState({}, document.title, cleanUrl);
		}
	}

})();
