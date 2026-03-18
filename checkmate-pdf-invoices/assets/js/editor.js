/**
 * Checkmate PDF Invoices - Template Editor JavaScript
 * 
 * Main editor functionality: drag & drop, block management, preview
 */

(function() {
	'use strict';

	// Wait for DOM
	document.addEventListener('DOMContentLoaded', initEditor);

	// Editor state
	const state = {
		template: null,
		blocks: {},
		categories: {},
		selectedBlock: null,
		isDirty: false,
		history: [],
		historyIndex: -1,
		maxHistory: 50,
		zoom: 100,
		view: 'editor',
	};

	// DOM Elements cache
	const elements = {};

	// Config from PHP
	let config = {};

	/**
	 * Initialize the editor
	 */
	function initEditor() {
		console.log('Initializing editor...');
		
		// Parse config from PHP
		const dataEl = document.getElementById('editor-data');
		if (dataEl) {
			try {
				config = JSON.parse(dataEl.textContent);
				state.template = config.template;
				state.blocks = config.blocks;
				state.categories = config.categories;
				normalizeTemplateDefaults();
				console.log('Loaded template:', state.template);
				console.log('Initial blocks:', state.template.blocks);
			} catch (e) {
				console.error('Failed to parse editor data:', e);
				return;
			}
		}

		// Cache DOM elements
		cacheElements();

		// Initialize event listeners
		initEventListeners();

		// Render initial blocks
		renderBlocks();

		// Initialize drag and drop
		initDragAndDrop();
		
		// Initialize toolbar hover behavior
		initToolbarHover();
		
		// Initialize column resize
		initColumnResize();

		// Save initial state to history
		saveToHistory();

		// Check for unsaved changes on leave
		window.addEventListener('beforeunload', handleBeforeUnload);
	}

	function normalizeTemplateDefaults() {
		if (!state.template) return;

		// Normalize top-level template fields
		if (!state.template.document_type) state.template.document_type = config?.template?.document_type || 'invoice';
		if (!state.template.name) state.template.name = config?.template?.name || '';
		if (!Array.isArray(state.template.blocks)) state.template.blocks = [];

		// Normalize page settings so newly added keys always exist
		const defaultPs = config?.defaultPageSettings || {};
		state.template.page_settings = { ...defaultPs, ...(state.template.page_settings || {}) };

		// Normalize block attributes recursively
		state.template.blocks.forEach(applyBlockDefaultsRecursive);
	}

	function normalizeTemplateForSave(template) {
		if (!template) return template;
		// Deep clone to avoid mutating editor state / undo history.
		const cloned = JSON.parse(JSON.stringify(template));

		// Normalize page settings
		const defaultPs = config?.defaultPageSettings || {};
		cloned.page_settings = { ...defaultPs, ...(cloned.page_settings || {}) };

		// Normalize basic template fields (defensive for older saved templates)
		if (!cloned.document_type) cloned.document_type = config?.template?.document_type || 'invoice';
		if (!cloned.name) cloned.name = config?.template?.name || '';
		if (!Array.isArray(cloned.blocks)) cloned.blocks = [];

		if (Array.isArray(cloned.blocks)) {
			cloned.blocks.forEach(applyBlockDefaultsRecursive);
		}
		return cloned;
	}

	function applyBlockDefaultsRecursive(block) {
		if (!block || !block.type) return;

		// Migration: image blocks should use `url` as the image source.
		// Older templates (or accidental use of background image controls) may have `backgroundImage` set instead.
		if (block.type === 'image' && block.attributes) {
			const url = (typeof block.attributes.url === 'string') ? block.attributes.url.trim() : '';
			const bg = (typeof block.attributes.backgroundImage === 'string') ? block.attributes.backgroundImage.trim() : '';
			if (!url && bg) {
				block.attributes.url = bg;
				delete block.attributes.backgroundImage;
				delete block.attributes.backgroundRepeat;
				delete block.attributes.backgroundPosition;
				delete block.attributes.backgroundSize;
			}
		}

		// Legacy migration: older templates used `color` for text color.
		// Preserve divider's `color` by only migrating known text-like blocks.
		if (block.attributes && typeof block.attributes.textColor === 'undefined' && typeof block.attributes.color !== 'undefined') {
			const migrateTypes = ['text', 'heading', 'document-title', 'footer'];
			if (migrateTypes.includes(block.type)) {
				block.attributes.textColor = block.attributes.color;
				delete block.attributes.color;
			}
		}

		const def = state.blocks?.[block.type];
		if (def && def.attributes) {
			if (!block.attributes) block.attributes = {};
			Object.entries(def.attributes).forEach(([key, attrDef]) => {
				if (typeof block.attributes[key] === 'undefined') {
					block.attributes[key] = attrDef?.default;
				}
			});
		}
		if (Array.isArray(block.children)) {
			block.children.forEach(applyBlockDefaultsRecursive);
		}
	}

	function getAttrWithDefault(blockType, attrs, key) {
		if (attrs && typeof attrs[key] !== 'undefined') return attrs[key];
		const def = state.blocks?.[blockType]?.attributes?.[key];
		return def ? def.default : undefined;
	}

	function buildBaseStyleCss(blockType, attrs) {
		const styleRules = [];

		const paddingTop = getAttrWithDefault(blockType, attrs, 'paddingTop');
		const paddingRight = getAttrWithDefault(blockType, attrs, 'paddingRight');
		const paddingBottom = getAttrWithDefault(blockType, attrs, 'paddingBottom');
		const paddingLeft = getAttrWithDefault(blockType, attrs, 'paddingLeft');
		const marginTop = getAttrWithDefault(blockType, attrs, 'marginTop');
		const marginRight = getAttrWithDefault(blockType, attrs, 'marginRight');
		const marginBottom = getAttrWithDefault(blockType, attrs, 'marginBottom');
		const marginLeft = getAttrWithDefault(blockType, attrs, 'marginLeft');

		if (typeof paddingTop !== 'undefined') styleRules.push(`padding-top: ${paddingTop}px`);
		if (typeof paddingRight !== 'undefined') styleRules.push(`padding-right: ${paddingRight}px`);
		if (typeof paddingBottom !== 'undefined') styleRules.push(`padding-bottom: ${paddingBottom}px`);
		if (typeof paddingLeft !== 'undefined') styleRules.push(`padding-left: ${paddingLeft}px`);
		if (typeof marginTop !== 'undefined') styleRules.push(`margin-top: ${marginTop}px`);
		if (typeof marginRight !== 'undefined') styleRules.push(`margin-right: ${marginRight}px`);
		if (typeof marginBottom !== 'undefined') styleRules.push(`margin-bottom: ${marginBottom}px`);
		if (typeof marginLeft !== 'undefined') styleRules.push(`margin-left: ${marginLeft}px`);

		const backgroundColor = getAttrWithDefault(blockType, attrs, 'backgroundColor');
		if (backgroundColor) styleRules.push(`background-color: ${backgroundColor}`);

		// Background images on image blocks are confusing (it can look like the image is set
		// while the actual `url` is empty). Treat image blocks as content-only.
		if (blockType !== 'image') {
			const backgroundImage = getAttrWithDefault(blockType, attrs, 'backgroundImage');
			if (backgroundImage) {
				const backgroundRepeat = getAttrWithDefault(blockType, attrs, 'backgroundRepeat') || 'no-repeat';
				const backgroundPosition = getAttrWithDefault(blockType, attrs, 'backgroundPosition') || 'top left';
				const backgroundSize = getAttrWithDefault(blockType, attrs, 'backgroundSize') || 'auto';
				// Avoid quotes so this string can be embedded safely into HTML attributes if needed.
				styleRules.push(`background-image: url(${backgroundImage})`);
				styleRules.push(`background-repeat: ${backgroundRepeat}`);
				styleRules.push(`background-position: ${backgroundPosition}`);
				styleRules.push(`background-size: ${backgroundSize}`);
			}
		}

		// Typography (apply on wrapper so inner text inherits)
		const textColor = getAttrWithDefault(blockType, attrs, 'textColor');
		if (textColor) styleRules.push(`color: ${textColor}`);

		const textTransform = getAttrWithDefault(blockType, attrs, 'textTransform');
		if (textTransform && textTransform !== 'none') styleRules.push(`text-transform: ${textTransform}`);

		const fontWeight = getAttrWithDefault(blockType, attrs, 'fontWeight');
		if (fontWeight && fontWeight !== 'normal') styleRules.push(`font-weight: ${fontWeight}`);

		const fontStyle = getAttrWithDefault(blockType, attrs, 'fontStyle');
		if (fontStyle && fontStyle !== 'normal') styleRules.push(`font-style: ${fontStyle}`);

		const textDecoration = getAttrWithDefault(blockType, attrs, 'textDecoration');
		if (textDecoration && textDecoration !== 'none') styleRules.push(`text-decoration: ${textDecoration}`);

		return styleRules.join('; ');
	}

	/**
	 * Cache DOM elements
	 */
	function cacheElements() {
		elements.wrap = document.querySelector('.checkmate-editor-wrap');
		elements.canvasWrap = document.querySelector('.editor-canvas-wrap');
		elements.canvasContent = document.getElementById('canvas-content');
		elements.canvasEmpty = document.getElementById('canvas-empty');
		elements.pdfCanvas = document.getElementById('pdf-canvas');
		elements.canvasContainer = document.getElementById('canvas-container');
		elements.blockToolbar = document.getElementById('block-toolbar');
		elements.dropIndicator = document.getElementById('drop-indicator');
		elements.blockSettings = document.getElementById('block-settings');
		elements.blockSettingsContent = document.getElementById('block-settings-content');
		elements.blockSettingsTitle = document.getElementById('block-settings-title');
		elements.pageSettings = document.getElementById('page-settings');
		elements.previewPanel = document.getElementById('preview-panel');
		elements.previewIframe = document.getElementById('preview-iframe');
		elements.templateName = document.getElementById('template-name');
		elements.documentType = document.getElementById('document-type');
		elements.zoomLevel = document.getElementById('zoom-level');
		elements.btnUndo = document.getElementById('btn-undo');
		elements.btnRedo = document.getElementById('btn-redo');
	}

	/**
	 * Initialize event listeners
	 */
	function initEventListeners() {
		// Header buttons
		document.getElementById('btn-save-template')?.addEventListener('click', saveTemplate);
		document.getElementById('btn-download-pdf')?.addEventListener('click', downloadPDF);
		elements.btnUndo?.addEventListener('click', undo);
		elements.btnRedo?.addEventListener('click', redo);

		// View toggle
		document.querySelectorAll('.view-btn').forEach(btn => {
			btn.addEventListener('click', () => switchView(btn.dataset.view));
		});

		// Zoom controls
		document.getElementById('zoom-in')?.addEventListener('click', () => setZoom(state.zoom + 10));
		document.getElementById('zoom-out')?.addEventListener('click', () => setZoom(state.zoom - 10));
		document.getElementById('zoom-fit')?.addEventListener('click', zoomToFit);

		// Block category toggles
		document.querySelectorAll('.category-header').forEach(header => {
			header.addEventListener('click', () => {
				header.closest('.blocks-category').classList.toggle('collapsed');
			});
		});

		// Block search
		const searchToggle = document.querySelector('.panel-search-toggle');
		const searchWrap = document.querySelector('.blocks-search');
		const searchInput = document.getElementById('blocks-search-input');

		searchToggle?.addEventListener('click', () => {
			searchWrap.style.display = searchWrap.style.display === 'none' ? 'block' : 'none';
			if (searchWrap.style.display === 'block') {
				searchInput?.focus();
			}
		});

		searchInput?.addEventListener('input', filterBlocks);

		// Template name & type changes
		elements.templateName?.addEventListener('input', () => {
			state.template.name = elements.templateName.value;
			markDirty();
		});

		elements.documentType?.addEventListener('change', () => {
			state.template.document_type = elements.documentType.value;
			markDirty();
		});

		// Page settings
		initPageSettingsListeners();

		// Canvas click (deselect)
		elements.canvasContent?.addEventListener('click', (e) => {
			if (e.target === elements.canvasContent || e.target === elements.canvasEmpty) {
				deselectBlock();
			}
		});

		// Keyboard shortcuts
		document.addEventListener('keydown', handleKeyboard);

		// Delete block button
		document.getElementById('btn-delete-block')?.addEventListener('click', () => {
			if (state.selectedBlock) {
				deleteBlock(state.selectedBlock);
			}
		});

		// Block toolbar actions
		elements.blockToolbar?.addEventListener('click', (e) => {
			const btn = e.target.closest('.toolbar-btn');
			if (!btn) return;
			const action = btn.dataset.action;
			const toolbarBlockId = elements.blockToolbar?.dataset?.blockId;
			const targetBlockId = toolbarBlockId || state.selectedBlock;
			if (!action || !targetBlockId) return;
			handleBlockAction(action, targetBlockId);
		});

		// Preview refresh
		document.getElementById('btn-refresh-preview')?.addEventListener('click', refreshPreview);
	}

	/**
	 * Initialize page settings listeners
	 */
	function initPageSettingsListeners() {
		const settings = state.template.page_settings;

		// Paper size
		document.getElementById('paper-size')?.addEventListener('change', (e) => {
			settings.paperSize = e.target.value;
			updateCanvasDimensions();
			markDirty();
		});

		// Orientation
		document.querySelectorAll('.orientation-btn').forEach(btn => {
			btn.addEventListener('click', () => {
				document.querySelectorAll('.orientation-btn').forEach(b => b.classList.remove('active'));
				btn.classList.add('active');
				settings.orientation = btn.dataset.orientation;
				updateCanvasDimensions();
				markDirty();
			});
		});

		// Margins
		['top', 'right', 'bottom', 'left'].forEach(side => {
			const input = document.getElementById(`margin-${side}`);
			input?.addEventListener('input', () => {
				settings[`margin${side.charAt(0).toUpperCase() + side.slice(1)}`] = parseInt(input.value) || 0;
				updateCanvasMargins();
				markDirty();
			});
		});

		// Font family
		document.getElementById('font-family')?.addEventListener('change', (e) => {
			settings.fontFamily = e.target.value;
			updateCanvasStyles();
			markDirty();
		});

		// Base font size
		document.getElementById('base-font-size')?.addEventListener('input', (e) => {
			settings.baseFontSize = parseInt(e.target.value) || 9;
			updateCanvasStyles();
			markDirty();
		});

		// Colors
		const textColor = document.getElementById('text-color');
		const textColorHex = document.getElementById('text-color-hex');
		const bgColor = document.getElementById('bg-color');
		const bgColorHex = document.getElementById('bg-color-hex');

		textColor?.addEventListener('input', () => {
			settings.textColor = textColor.value;
			textColorHex.value = textColor.value;
			updateCanvasStyles();
			markDirty();
		});

		textColorHex?.addEventListener('input', () => {
			if (/^#[0-9A-Fa-f]{6}$/.test(textColorHex.value)) {
				settings.textColor = textColorHex.value;
				textColor.value = textColorHex.value;
				updateCanvasStyles();
				markDirty();
			}
		});

		bgColor?.addEventListener('input', () => {
			settings.backgroundColor = bgColor.value;
			bgColorHex.value = bgColor.value;
			updateCanvasStyles();
			markDirty();
		});

		bgColorHex?.addEventListener('input', () => {
			if (/^#[0-9A-Fa-f]{6}$/.test(bgColorHex.value)) {
				settings.backgroundColor = bgColorHex.value;
				bgColor.value = bgColorHex.value;
				updateCanvasStyles();
				markDirty();
			}
		});

		// Page background image
		const pageBgImage = document.getElementById('page-bg-image');
		const pageBgImagePreview = document.getElementById('page-bg-image-preview');
		const pageBgRepeat = document.getElementById('page-bg-repeat');
		const pageBgPosition = document.getElementById('page-bg-position');
		const pageBgSize = document.getElementById('page-bg-size');
		const pageBgRepeatGroup = document.getElementById('page-bg-repeat-group');
		const pageBgPositionGroup = document.getElementById('page-bg-position-group');
		const pageBgSizeGroup = document.getElementById('page-bg-size-group');

		// Upload button for page background
		document.querySelector('.upload-image-btn[data-target="page-bg-image"]')?.addEventListener('click', () => {
			const frame = wp.media({
				title: 'Select Background Image',
				multiple: false,
				library: { type: 'image' }
			});
			frame.on('select', () => {
				const attachment = frame.state().get('selection').first().toJSON();
				pageBgImage.value = attachment.url;
				const previewImg = pageBgImagePreview.querySelector('img');
				if (previewImg) previewImg.src = attachment.url;
				pageBgImagePreview.style.display = 'block';
				// Show additional settings
				if (pageBgRepeatGroup) pageBgRepeatGroup.style.display = '';
				if (pageBgPositionGroup) pageBgPositionGroup.style.display = '';
				if (pageBgSizeGroup) pageBgSizeGroup.style.display = '';
				settings.backgroundImage = attachment.url;
				updateCanvasStyles();
				markDirty();
			});
			frame.open();
		});

		// Remove button for page background
		document.querySelector('.remove-image[data-target="page-bg-image"]')?.addEventListener('click', () => {
			pageBgImage.value = '';
			pageBgImagePreview.style.display = 'none';
			// Hide additional settings
			if (pageBgRepeatGroup) pageBgRepeatGroup.style.display = 'none';
			if (pageBgPositionGroup) pageBgPositionGroup.style.display = 'none';
			if (pageBgSizeGroup) pageBgSizeGroup.style.display = 'none';
			settings.backgroundImage = '';
			updateCanvasStyles();
			markDirty();
		});

		pageBgRepeat?.addEventListener('change', () => {
			settings.backgroundRepeat = pageBgRepeat.value;
			updateCanvasStyles();
			markDirty();
		});

		pageBgPosition?.addEventListener('change', () => {
			settings.backgroundPosition = pageBgPosition.value;
			updateCanvasStyles();
			markDirty();
		});

		pageBgSize?.addEventListener('change', () => {
			settings.backgroundSize = pageBgSize.value;
			updateCanvasStyles();
			markDirty();
		});
	}

	/**
	 * Initialize drag and drop
	 */
	function initDragAndDrop() {
		// Block items in sidebar
		document.querySelectorAll('.block-item').forEach(item => {
			item.addEventListener('dragstart', handleDragStart);
			item.addEventListener('dragend', handleDragEnd);
		});

		// Canvas drop zones
		elements.canvasContent?.addEventListener('dragover', handleDragOver);
		elements.canvasContent?.addEventListener('dragleave', handleDragLeave);
		elements.canvasContent?.addEventListener('drop', handleDrop);
	}

	/**
	 * Render all blocks
	 */
	function renderBlocks() {
		if (!elements.canvasContent) return;

		const blocks = state.template.blocks || [];
		
		if (blocks.length === 0) {
			elements.canvasEmpty.style.display = 'flex';
			elements.canvasContent.querySelectorAll('.template-block').forEach(el => el.remove());
			return;
		}

		elements.canvasEmpty.style.display = 'none';

		// Clear existing blocks
		elements.canvasContent.querySelectorAll('.template-block').forEach(el => el.remove());

		// Render each block
		blocks.forEach(block => {
			const blockEl = renderBlock(block);
			if (blockEl) {
				elements.canvasContent.appendChild(blockEl);
			}
		});
	}

	/**
	 * Render a single block
	 */
	function renderBlock(block) {
		const blockDef = state.blocks[block.type];
		if (!blockDef) {
			console.warn('Unknown block type:', block.type);
			return null;
		}

		const el = document.createElement('div');
		el.className = `template-block block-${block.type}`;
		el.dataset.blockId = block.id;
		el.dataset.blockType = block.type;

		// Apply base style attributes (padding, margin, background) with defaults
		const cssText = buildBaseStyleCss(block.type, block.attributes || {});
		if (cssText) el.style.cssText = cssText;

		// Apply block-specific rendering
		const content = renderBlockContent(block, blockDef);
		el.innerHTML = content;

		// Add event listeners - for row blocks, allow inner block selection
		el.addEventListener('click', (e) => {
			// Allow column move buttons to handle their own clicks
			if (e.target.closest('.column-move-btn')) {
				return; // Don't stop propagation, let the button handler run
			}
			
			e.stopPropagation();
			
			// Check if click was on an inner block (for row/column blocks)
			const innerBlock = e.target.closest('.template-block[data-block-id]');
			if (innerBlock && innerBlock !== el) {
				// Clicked on an inner block - select it instead
				selectBlock(innerBlock.dataset.blockId);
				return;
			}
			
			// Check if click was on a column (for adding blocks to column)
			const column = e.target.closest('[data-column-id]');
			if (column && e.target.closest('.column-placeholder')) {
				// Clicked on empty column placeholder - could show block picker
				return;
			}
			
			selectBlock(block.id);
		});

		// For row blocks, use mouseover event delegation for inner blocks
		if (block.type === 'row') {
			el.addEventListener('mouseover', (e) => {
				const columnHit = e.target.closest('.column-toolbar-hit');
				if (columnHit && columnHit.dataset.columnId) {
					e.stopPropagation();
					showBlockToolbar(columnHit, columnHit.dataset.columnId);
					return;
				}

				const innerBlock = e.target.closest('.template-block[data-block-id]');
				if (innerBlock && innerBlock !== el) {
					e.stopPropagation();
					showBlockToolbar(innerBlock, innerBlock.dataset.blockId);
				}

				// If not hovering a child block, show column tools when hovering a column cell
				const columnCell = e.target.closest('td[data-column-id]');
				if (columnCell && !innerBlock) {
					e.stopPropagation();
					showBlockToolbar(columnCell, columnCell.dataset.columnId);
				}
			});
		}

		el.addEventListener('mouseenter', () => showBlockToolbar(el, block.id));
		el.addEventListener('mouseleave', hideBlockToolbar);

		// Make blocks within canvas draggable for reordering
		el.draggable = true;
		el.addEventListener('dragstart', (e) => handleBlockDragStart(e, block.id));
		el.addEventListener('dragend', handleDragEnd);

		return el;
	}

	function renderDynamicTokensForPreview(html) {
		if (!html || typeof html !== 'string') return html;
		if (html.indexOf('{') === -1) return html;

		const now = new Date();
		const replacements = {
			'{shop_name}': 'My Shop',
			'{shop_email}': 'shop@example.com',
			'{shop_phone}': '+1 (555) 123-4567',
			'{site_url}': 'https://example.com',
			'{current_date}': formatDate(now),
			'{current_year}': String(now.getFullYear()),
			'{order_number}': '#WC-12345',
			'{order_date}': formatDate(now),
			'{order_total}': '$220.96',
			'{payment_method}': 'Credit Card (Stripe)',
			'{shipping_method}': 'Flat Rate Shipping',
			'{customer_name}': 'John Doe',
			'{customer_address}': '123 Main St<br>City, State 12345',
			'{customer_email}': 'john.doe@example.com',
			'{customer_phone}': '+1 (555) 987-6543',
			'{billing_name}': 'John Doe',
			'{billing_address}': '123 Main St<br>City, State 12345',
			'{billing_email}': 'john.doe@example.com',
			'{billing_phone}': '+1 (555) 987-6543',
			'{shipping_name}': 'John Doe',
			'{shipping_address}': '456 Shipping Ave<br>City, State 12345',
			'{shipping_email}': 'john.doe@example.com',
			'{shipping_phone}': '+1 (555) 987-6543',
		};

		let out = html;
		Object.keys(replacements).forEach((token) => {
			if (out.includes(token)) {
				out = out.split(token).join(replacements[token]);
			}
		});
		return out;
	}

	/**
	 * Render block content based on type
	 */
	function renderBlockContent(block, blockDef) {
		const attrs = block.attributes || {};
		
		switch (block.type) {
			case 'row':
				return renderRow(block);
			
			case 'spacer':
				return `<div style="height: ${attrs.height || 20}px;"></div>`;
			
			case 'divider':
				return `<hr style="border: none; border-top: ${attrs.thickness || 1}px ${attrs.style || 'solid'} ${attrs.color || '#ccc'}; margin: 0;">`;
			
			case 'logo':
				return renderLogo(attrs);
			
			case 'text':
				return `<div style="font-size: ${attrs.fontSize || 9}pt; text-align: ${normalizeTextAlign(attrs.align || 'left')};">${renderDynamicTokensForPreview(attrs.content || 'Enter text...')}</div>`;
			
			case 'heading':
				const levels = { h1: '18pt', h2: '14pt', h3: '12pt', h4: '10pt' };
				return `<div style="font-size: ${levels[attrs.level] || '14pt'}; text-align: ${normalizeTextAlign(attrs.align || 'left')}; margin: 0;">${renderDynamicTokensForPreview(attrs.content || 'Heading')}</div>`;
			
			case 'image':
				return renderImage(attrs);
			
			case 'document-title':
				return `<div style="font-size: ${attrs.fontSize || 16}pt; text-align: ${normalizeTextAlign(attrs.align || 'left')};">${getDocumentTitle()}</div>`;
			
			case 'document-number':
				return renderLabelValue(attrs.showLabel ? (attrs.label || 'Invoice #:') : '', '#INV-2024-001', attrs.fontSize || 9, attrs);
			
			case 'document-date':
				return renderLabelValue(attrs.showLabel ? (attrs.label || 'Date:') : '', formatDate(new Date(), attrs.format), attrs.fontSize || 9, attrs);
			
			case 'shop-info':
				return renderShopInfo(attrs);
			
			case 'billing-address':
			case 'shipping-address':
				return renderAddress(block.type, attrs);
			
			case 'order-number':
				return renderLabelValue(attrs.showLabel ? (attrs.label || 'Order #:') : '', '#WC-12345', attrs.fontSize || 9, attrs);
			
			case 'order-date':
				return renderLabelValue(attrs.showLabel ? (attrs.label || 'Order Date:') : '', formatDate(new Date(), attrs.format), attrs.fontSize || 9, attrs);
			
			case 'payment-method':
				return renderLabelValue(attrs.showLabel ? (attrs.label || 'Payment:') : '', 'Credit Card (Stripe)', attrs.fontSize || 9, attrs);
			
			case 'shipping-method':
				return renderLabelValue(attrs.showLabel ? (attrs.label || 'Shipping:') : '', 'Flat Rate Shipping', attrs.fontSize || 9, attrs);
			
			case 'customer-note':
				const cnAlign = normalizeTextAlign(attrs.align || 'left');
				if (attrs.hideIfEmpty) {
					const titleText = renderDynamicTokensForPreview(attrs.title || 'Note:');
					const titleStyle = buildTitleStyleCss(attrs);
					return `<div style="font-size: ${attrs.fontSize || 9}pt; text-align: ${cnAlign};">${attrs.showTitle ? `<span style="${titleStyle}">${titleText}</span> ` : ''}<em style="color: #888;">No customer note</em></div>`;
				}
				{
					const titleText = renderDynamicTokensForPreview(attrs.title || 'Note:');
					const titleStyle = buildTitleStyleCss(attrs);
					return `<div style="font-size: ${attrs.fontSize || 9}pt; text-align: ${cnAlign};">${attrs.showTitle ? `<span style="${titleStyle}">${titleText}</span> ` : ''}Sample customer note text...</div>`;
				}
			
			case 'items-table':
				return renderItemsTable(attrs);
			
			case 'totals-table':
				return renderTotalsTable(attrs);
			
			case 'notes':
				return `<div style="font-size: ${attrs.fontSize || 8}pt; text-align: ${normalizeTextAlign(attrs.align || 'left')};">${attrs.showTitle ? `<span style="${buildTitleStyleCss(attrs)}">${renderDynamicTokensForPreview(attrs.title || 'Notes:')}</span><br>` : ''}${renderDynamicTokensForPreview(attrs.content || 'Thank you for your business!')}</div>`;
			
			case 'footer':
				return `<div style="font-size: ${attrs.fontSize || 7}pt; text-align: ${normalizeTextAlign(attrs.align || 'center')};">${renderDynamicTokensForPreview(attrs.content || 'Footer content')}</div>`;
			
			default:
				return `<div class="block-placeholder">${blockDef.title}</div>`;
		}
	}

	/**
	 * Render row block with columns
	 */
	function renderRow(block) {
		const attrs = block.attributes || {};
		const children = block.children || [];
		
		let html = '<div class="row-container" data-row-id="' + block.id + '"><table style="width: 100%; border-collapse: collapse; table-layout: fixed;"><tr>';
		
		children.forEach((child, colIndex) => {
			const childAttrs = child.attributes || {};
			const width = childAttrs.width || 50;
			const gapHalf = (attrs.gap || 10) / 2;
			// Remove padding on first/last columns outer edge
			const paddingLeft = colIndex === 0 ? 0 : gapHalf;
			const paddingRight = colIndex === children.length - 1 ? 0 : gapHalf;
			html += `<td data-column-id="${child.id}" data-column-index="${colIndex}" data-block-id="${child.id}" data-block-type="column" data-parent-row-id="${block.id}" style="width: ${width}%; vertical-align: ${attrs.verticalAlign || 'top'}; padding: 0; padding-left: ${paddingLeft}px; padding-right: ${paddingRight}px; position: relative;">`;
			html += `<div class="column-toolbar-hit" data-column-id="${child.id}" data-block-type="column"></div>`;
			
			// Render child blocks
			const childBlocks = child.children || [];
			childBlocks.forEach(cb => {
				const cbDef = state.blocks[cb.type];
				if (cbDef) {
					const childCssText = buildBaseStyleCss(cb.type, cb.attributes || {});
					const childCssAttr = childCssText ? ` style="${childCssText.replace(/\"/g, '&quot;')}"` : '';
					html += `<div class="template-block block-${cb.type}" data-block-id="${cb.id}" data-block-type="${cb.type}"${childCssAttr}>`;
					html += renderBlockContent(cb, cbDef);
					html += '</div>';
				}
			});
			
			if (childBlocks.length === 0) {
				html += `<div class="column-placeholder" data-column-id="${child.id}">
					<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-bottom: 8px; opacity: 0.5;">
						<path d="M12 5v14M5 12h14"/>
					</svg>
					<div>Drop blocks here</div>
				</div>`;
			}
			
			// Add resize handle between columns (not after last column)
			if (colIndex < children.length - 1) {
				html += `<div class="column-resize-handle" data-row-id="${block.id}" data-col-index="${colIndex}"></div>`;
			}
			
			html += '</td>';
		});
		
		html += '</tr></table></div>';
		return html;
	}

	/**
	 * Render logo block
	 */
	function renderLogo(attrs) {
		const maxHeight = attrs.maxHeight || 80;
		const align = normalizeTextAlign(attrs.align || 'left');
		const source = attrs.source || 'site';
		const customUrl = (attrs.customUrl || '').trim();
		const siteLogoUrl = (config.siteLogoUrl || '').trim();
		const siteName = (config.siteName || '').trim();

		let logoUrl = '';
		if (source === 'custom') {
			logoUrl = customUrl;
		} else {
			logoUrl = siteLogoUrl;
		}

		if (logoUrl) {
			return `<div style="text-align: ${align};"><img src="${escapeHtml(logoUrl)}" alt="${escapeHtml(siteName || 'Logo')}" style="max-height: ${maxHeight}px; height: auto; max-width: 100%;"></div>`;
		}

		// Fallback: show site name when no logo image is available.
		return `<div style="text-align: ${align};"><div style="display: inline-block; font-weight: 700; font-size: 16px; color: #111827;">${escapeHtml(siteName || 'Shop')}</div></div>`;
	}

	/**
	 * Render image block
	 */
	function renderImage(attrs) {
		const url = (attrs.url || '').trim();
		// Fallback: if user accidentally set base backgroundImage instead of the image URL,
		// show it as the image source so the block doesn't look “broken”.
		const bgUrl = (attrs.backgroundImage || '').trim();
		const src = url || bgUrl;
		const alt = attrs.alt || '';
		const width = attrs.width || 100;
		const maxHeight = attrs.maxHeight || 200;
		const align = normalizeTextAlign(attrs.align || 'left');
		
		if (!src) {
			return `<div style="text-align: ${align}; padding: 20px; border: 2px dashed #ccc; color: #999; border-radius: 4px;">
				<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="opacity: 0.5; margin-bottom: 8px;">
					<rect x="3" y="3" width="18" height="18" rx="2"/>
					<circle cx="8.5" cy="8.5" r="1.5"/>
					<polyline points="21 15 16 10 5 21"/>
				</svg>
				<div>Click to add image</div>
			</div>`;
		}
		
		return `<div style="text-align: ${align};"><img src="${src}" alt="${alt}" style="max-width: ${width}%; max-height: ${maxHeight}px; height: auto;"></div>`;
	}

	/**
	 * Render label: value format
	 */
	function buildLabelStyleCss(attrs = {}) {
		const style = [];
		const fs = Number(attrs.labelFontSize || 0);
		if (fs > 0) style.push(`font-size: ${fs}pt`);
		if (attrs.labelFontWeight) style.push(`font-weight: ${attrs.labelFontWeight}`);
		else style.push('font-weight: bold');
		if (attrs.labelFontStyle) style.push(`font-style: ${attrs.labelFontStyle}`);
		if (attrs.labelTextDecoration) style.push(`text-decoration: ${attrs.labelTextDecoration}`);
		if (attrs.labelTextTransform) style.push(`text-transform: ${attrs.labelTextTransform}`);
		if (attrs.labelTextColor) style.push(`color: ${attrs.labelTextColor}`);
		return style.join('; ');
	}

	function buildTitleStyleCss(attrs = {}) {
		const style = [];
		const fs = Number(attrs.titleFontSize || 0);
		if (fs > 0) style.push(`font-size: ${fs}pt`);
		if (attrs.titleFontWeight) style.push(`font-weight: ${attrs.titleFontWeight}`);
		else style.push('font-weight: bold');
		if (attrs.titleFontStyle) style.push(`font-style: ${attrs.titleFontStyle}`);
		if (attrs.titleTextDecoration) style.push(`text-decoration: ${attrs.titleTextDecoration}`);
		if (attrs.titleTextTransform) style.push(`text-transform: ${attrs.titleTextTransform}`);
		if (attrs.titleTextColor) style.push(`color: ${attrs.titleTextColor}`);
		return style.join('; ');
	}

	function normalizeTextAlign(align) {
		if (!align) return 'left';
		if (align === 'start') return 'left';
		if (align === 'end') return 'right';
		if (align === 'left' || align === 'center' || align === 'right') return align;
		return 'left';
	}

	function renderLabelValue(label, value, fontSize = 9, attrs = {}) {
		const safeLabel = label ? renderDynamicTokensForPreview(label) : '';
		const labelStyle = buildLabelStyleCss(attrs);
		const textAlign = normalizeTextAlign(attrs.align || 'left');
		const labelPosition = attrs.labelPosition || 'left';
		if (safeLabel) {
			if (labelPosition === 'right') {
				return `<div style="font-size: ${fontSize}pt; text-align: ${textAlign};">${value} <span style="${labelStyle}">${safeLabel}</span></div>`;
			}
			return `<div style="font-size: ${fontSize}pt; text-align: ${textAlign};"><span style="${labelStyle}">${safeLabel}</span> ${value}</div>`;
		}
		return `<div style="font-size: ${fontSize}pt; text-align: ${textAlign};">${value}</div>`;
	}

	/**
	 * Render shop info block
	 */
	function renderShopInfo(attrs) {
		let html = `<div style="font-size: ${attrs.fontSize || 9}pt; text-align: ${normalizeTextAlign(attrs.align || 'left')};">`;
		if (attrs.showName) html += '<strong>Your Company Name</strong><br>';
		if (attrs.showAddress) html += '123 Business Street<br>City, State 12345<br>';
		if (attrs.showPhone) html += 'Phone: (555) 123-4567<br>';
		if (attrs.showEmail) html += 'Email: info@company.com';
		html += '</div>';
		return html;
	}

	/**
	 * Render address block
	 */
	function renderAddress(type, attrs) {
		const isBilling = type === 'billing-address';
		const textAlign = normalizeTextAlign(attrs.align || 'left');
		let html = `<div style="font-size: ${attrs.fontSize || 9}pt; text-align: ${textAlign};">`;
		
		if (attrs.showTitle) {
			const titleText = renderDynamicTokensForPreview(attrs.title || (isBilling ? 'Bill To:' : 'Ship To:'));
			html += `<span style="${buildTitleStyleCss(attrs)}">${titleText}</span><br>`;
		}
		
		html += 'John Doe<br>';
		html += '456 Customer Lane<br>';
		html += 'Anytown, ST 54321<br>';
		
		if (isBilling && attrs.showEmail) {
			html += 'john@example.com<br>';
		}
		if (attrs.showPhone) {
			html += '(555) 987-6543';
		}
		
		html += '</div>';
		return html;
	}

	/**
	 * Render items table
	 */
	function renderItemsTable(attrs) {
		const columns = attrs.columns || ['product', 'quantity', 'price'];
		const headerBg = attrs.headerBackground || '#000';
		const headerColor = attrs.headerColor || '#fff';
		const borderColor = attrs.borderColor || '#e0e0e0';
		const fontSize = attrs.fontSize || 9;
		const textAlign = normalizeTextAlign(attrs.textAlign || 'left');

		const columnLabels = {
			product: attrs.headerProductLabel || 'Product',
			sku: attrs.headerSkuLabel || 'SKU',
			quantity: attrs.headerQuantityLabel || 'Qty',
			price: attrs.headerPriceLabel || 'Price',
			total: attrs.headerTotalLabel || 'Total',
			weight: attrs.headerWeightLabel || 'Weight',
			tax: attrs.headerTaxLabel || 'Tax'
		};

		let html = `<table style="width: 100%; border-collapse: collapse; font-size: ${fontSize}pt;">`;
		
		if (attrs.showHeader) {
			html += '<thead><tr>';
			columns.forEach(col => {
				const lbl = renderDynamicTokensForPreview(columnLabels[col] || col);
				html += `<th style="background: ${headerBg}; color: ${headerColor}; padding: 8px 10px; text-align: ${textAlign}; font-weight: bold;">${lbl}</th>`;
			});
			html += '</tr></thead>';
		}

		html += '<tbody>';
		
		// Sample items
		const sampleItems = [
			{ product: 'Premium Widget', sku: 'WDG-001', quantity: 2, price: '$29.99', total: '$59.98', weight: '0.5 kg', tax: '$6.00' },
			{ product: 'Super Gadget Pro', sku: 'GDT-002', quantity: 1, price: '$149.99', total: '$149.99', weight: '1.2 kg', tax: '$15.00' },
		];

		sampleItems.forEach(item => {
			html += '<tr>';
			columns.forEach(col => {
				let value = item[col] || '';
				if (col === 'product' && attrs.showSku) {
					value += `<br><small style="color: #666;">SKU: ${item.sku}</small>`;
				}
				html += `<td style="padding: 8px 10px; border-bottom: 1px solid ${borderColor}; text-align: ${textAlign};">${value}</td>`;
			});
			html += '</tr>';
		});

		html += '</tbody></table>';
		return html;
	}

	/**
	 * Render totals table
	 */
	function renderTotalsTable(attrs) {
		const align = attrs.align || 'right';
		const width = attrs.width || 40;
		const fontSize = attrs.fontSize || 9;
		const labelStyle = buildLabelStyleCss(attrs);
		const textAlign = normalizeTextAlign(attrs.textAlign || 'right');
		const labelPosition = attrs.labelPosition || 'left';

		let html = `<table style="width: ${width}%; margin-${align === 'right' ? 'left' : 'right'}: auto; font-size: ${fontSize}pt;">`;

		function row(labelText, valueText, valueExtraStyle = '', labelExtraStyle = '') {
			const labelCell = `<td style="text-align: ${textAlign}; padding: 4px 10px; ${labelStyle} ${labelExtraStyle}">${labelText}</td>`;
			const valueCell = `<td style="text-align: ${textAlign}; padding: 4px 0; ${valueExtraStyle}">${valueText}</td>`;
			return labelPosition === 'right' ? `<tr>${valueCell}${labelCell}</tr>` : `<tr>${labelCell}${valueCell}</tr>`;
		}

		if (attrs.showSubtotal) {
			const lbl = renderDynamicTokensForPreview(attrs.labelSubtotal || 'Subtotal:');
			html += row(lbl, '$209.97');
		}
		if (attrs.showShipping) {
			const lbl = renderDynamicTokensForPreview(attrs.labelShipping || 'Shipping:');
			html += row(lbl, '$9.99');
		}
		if (attrs.showDiscount) {
			const lbl = renderDynamicTokensForPreview(attrs.labelDiscount || 'Discount:');
			const rawDiscountColor = attrs.discountTextColor || '';
			const discountColor = /^#([0-9a-f]{3}|[0-9a-f]{6})$/i.test(rawDiscountColor)
				? rawDiscountColor
				: '#c00';
			html += row(lbl, '-$20.00', `color: ${discountColor};`);
		}
		if (attrs.showTax) {
			const lbl = renderDynamicTokensForPreview(attrs.labelTax || 'Tax:');
			html += row(lbl, '$21.00');
		}
		if (attrs.showTotal) {
			const bold = attrs.totalBold ? 'font-weight: bold;' : '';
			const lbl = renderDynamicTokensForPreview(attrs.labelTotal || 'Total:');
			const labelExtraStyle = `border-top: 2px solid #000; ${bold}`;
			const valueExtraStyle = `border-top: 2px solid #000; ${bold}`;
			html += row(lbl, '$220.96', valueExtraStyle, labelExtraStyle);
		}

		html += '</table>';
		return html;
	}

	/**
	 * Get document title based on type
	 */
	function getDocumentTitle() {
		const titles = {
			'invoice': 'Invoice',
			'packing-slip': 'Packing Slip',
			'credit-note': 'Credit Note',
			'delivery-note': 'Delivery Note'
		};
		return titles[state.template.document_type] || 'Invoice';
	}

	/**
	 * Format date
	 */
	function formatDate(date, format = 'F j, Y') {
		const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
		const shortMonths = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

		// Render format in a single pass.
		// IMPORTANT: do not use chained `.replace()` because it can replace tokens
		// inside already-expanded text (e.g. replace('M', ...) inside "March" → "Mararch").
		let out = '';
		for (let i = 0; i < format.length; i++) {
			const ch = format[i];
			// Backslash escaping (PHP date-style): \M outputs literal "M".
			if (ch === '\\' && i + 1 < format.length) {
				out += format[i + 1];
				i++;
				continue;
			}

			switch (ch) {
				case 'F':
					out += months[date.getMonth()];
					break;
				case 'M':
					out += shortMonths[date.getMonth()];
					break;
				case 'j':
					out += String(date.getDate());
					break;
				case 'd':
					out += String(date.getDate()).padStart(2, '0');
					break;
				case 'Y':
					out += String(date.getFullYear());
					break;
				case 'y':
					out += String(date.getFullYear()).slice(-2);
					break;
				default:
					out += ch;
			}
		}
		return out;
	}

	/**
	 * Select a block
	 */
	function selectBlock(blockId) {
		// Deselect previous
		document.querySelectorAll('.template-block.selected').forEach(el => {
			el.classList.remove('selected');
		});

		state.selectedBlock = blockId;

		// Find and select element
		const el = document.querySelector(`[data-block-id="${blockId}"]`);
		if (el) {
			el.classList.add('selected');
		}

		// Show block settings
		showBlockSettings(blockId);
	}

	/**
	 * Deselect block
	 */
	function deselectBlock() {
		document.querySelectorAll('.template-block.selected').forEach(el => {
			el.classList.remove('selected');
		});
		state.selectedBlock = null;
		hideBlockSettings();
		hideBlockToolbar();
		
		// Show page settings when no block is selected
		if (elements.pageSettings) {
			elements.pageSettings.style.display = 'block';
		}
	}

	/**
	 * Show block settings panel
	 */
	function showBlockSettings(blockId) {
		const block = findBlock(state.template.blocks, blockId);
		if (!block) return;

		const blockDef = state.blocks[block.type];
		if (!blockDef) return;

		// Hide page settings when block is selected
		if (elements.pageSettings) {
			elements.pageSettings.style.display = 'none';
		}

		elements.blockSettings.style.display = 'block';
		elements.blockSettingsTitle.textContent = blockDef.title;

		// Render settings form
		let html = '';
		const attrs = block.attributes || {};

		const paddingKeys = ['paddingTop', 'paddingBottom', 'paddingLeft', 'paddingRight'];
		const marginKeys = ['marginTop', 'marginBottom', 'marginLeft', 'marginRight'];
		const spacingKeys = new Set([...paddingKeys, ...marginKeys]);

		const attrEntries = Object.entries(blockDef.attributes || {});
		const normalEntries = [];
		const presentPadding = [];
		const presentMargin = [];

		attrEntries.forEach(([key, attrDef]) => {
			// Ensure `content` field is always shown first (all blocks)
			if (key === 'content') {
				normalEntries.unshift([key, attrDef]);
				return;
			}
			if (spacingKeys.has(key)) {
				if (paddingKeys.includes(key)) presentPadding.push([key, attrDef]);
				if (marginKeys.includes(key)) presentMargin.push([key, attrDef]);
				return;
			}
			normalEntries.push([key, attrDef]);
		});

		const sanitizeRichHtmlForEditor = (html) => {
			if (!html) return '';
			return String(html)
				.replace(/<script[\s\S]*?>[\s\S]*?<\/script>/gi, '')
				.replace(/<style[\s\S]*?>[\s\S]*?<\/style>/gi, '');
		};

		const getDynamicTags = () => {
			return Array.isArray(config?.dynamicTags) ? config.dynamicTags : [];
		};

		const buildDynamicTagOptionsHtml = () => {
			const tags = getDynamicTags();
			if (!tags.length) return '';

			const groups = {};
			tags.forEach((t) => {
				const group = t.group || 'Fields';
				if (!groups[group]) groups[group] = [];
				groups[group].push(t);
			});

			let out = `<option value="">${escapeHtml('Insert field…')}</option>`;
			Object.keys(groups).forEach((groupName) => {
				out += `<optgroup label="${escapeHtml(groupName)}">`;
				groups[groupName].forEach((t) => {
					out += `<option value="${escapeHtml(t.token)}">${escapeHtml(t.label || t.token)}</option>`;
				});
				out += `</optgroup>`;
			});
			return out;
		};

		const initRichTextControls = () => {
			const wrap = elements.blockSettingsContent.querySelector('.cm-richtext-wrap[data-attr-key="content"]');
			if (!wrap) return;
			const editor = wrap.querySelector('.cm-richtext-editor');
			if (!editor) return;

			let lastSelectionRange = null;
			const rememberSelection = () => {
				const sel = window.getSelection();
				if (!sel || sel.rangeCount === 0) return;
				lastSelectionRange = sel.getRangeAt(0).cloneRange();
			};

			const restoreSelection = () => {
				if (!lastSelectionRange) return;
				const sel = window.getSelection();
				if (!sel) return;
				sel.removeAllRanges();
				sel.addRange(lastSelectionRange);
			};

			const insertTextAtCursor = (text) => {
				editor.focus();
				restoreSelection();
				if (document.queryCommandSupported && document.queryCommandSupported('insertText')) {
					document.execCommand('insertText', false, text);
					return;
				}
				const sel = window.getSelection();
				const range = sel && sel.rangeCount ? sel.getRangeAt(0) : null;
				if (!range) return;
				range.deleteContents();
				range.insertNode(document.createTextNode(text));
				range.collapse(false);
				sel.removeAllRanges();
				sel.addRange(range);
			};

			let debounceTimer = null;
			const scheduleUpdate = () => {
				clearTimeout(debounceTimer);
				debounceTimer = setTimeout(() => {
					updateBlockAttribute(blockId, 'content', editor.innerHTML, { saveHistory: false });
				}, 200);
			};

			wrap.querySelectorAll('.cm-rt-btn[data-cmd]').forEach((btn) => {
				btn.addEventListener('mousedown', (e) => {
					e.preventDefault();
					editor.focus();
					restoreSelection();
					document.execCommand(btn.dataset.cmd, false, null);
					rememberSelection();
					scheduleUpdate();
				});
			});

			const tagSelect = wrap.querySelector('.cm-rt-select');
			if (tagSelect) {
				tagSelect.addEventListener('mousedown', () => {
					rememberSelection();
				});
				tagSelect.addEventListener('change', () => {
					const token = tagSelect.value;
					if (!token) return;
					insertTextAtCursor(token);
					rememberSelection();
					scheduleUpdate();
					tagSelect.value = '';
				});
			}

			editor.addEventListener('keyup', rememberSelection);
			editor.addEventListener('mouseup', rememberSelection);
			editor.addEventListener('input', scheduleUpdate);
			editor.addEventListener('paste', (e) => {
				// Keep paste safe/clean: paste as plain text
				e.preventDefault();
				const text = (e.clipboardData || window.clipboardData).getData('text/plain');
				insertTextAtCursor(text);
				scheduleUpdate();
			});
			editor.addEventListener('blur', () => {
				clearTimeout(debounceTimer);
				updateBlockAttribute(blockId, 'content', editor.innerHTML, { saveHistory: true });
			});
		};

		const renderSettingGroup = (key, attrDef) => {
			const value = attrs[key] !== undefined ? attrs[key] : attrDef.default;
			let groupHtml = '';
			groupHtml += '<div class="setting-group">';
			groupHtml += `<label>${attrDef.label || key}</label>`;

			switch (attrDef.type) {
				case 'text':
				case 'textarea':
					groupHtml += `<input type="text" class="setting-input" data-attr="${key}" value="${escapeHtml(value)}">`;
					break;

				case 'richtext':
					if (block.type === 'text' && key === 'content') {
						const tags = getDynamicTags();
						groupHtml += `<div class="cm-richtext-wrap" data-attr-key="${key}">
							<div class="cm-richtext-toolbar">
								<button type="button" class="cm-rt-btn" data-cmd="bold">Bold</button>
								<button type="button" class="cm-rt-btn" data-cmd="italic">Italic</button>
								<button type="button" class="cm-rt-btn" data-cmd="underline">Underline</button>
								<button type="button" class="cm-rt-btn" data-cmd="strikeThrough">Strike</button>
								${tags.length ? `<select class="cm-rt-select">${buildDynamicTagOptionsHtml()}</select>` : ''}
							</div>
							<div class="cm-richtext-editor" contenteditable="true">${sanitizeRichHtmlForEditor(value)}</div>
						</div>`;
					} else {
						groupHtml += `<textarea class="setting-input" data-attr="${key}" rows="3">${escapeHtml(value)}</textarea>`;
					}
					break;

				case 'number':
					groupHtml += `<input type="number" class="setting-input" data-attr="${key}" value="${value}" min="${attrDef.min || 0}" max="${attrDef.max || 1000}">`;
					break;

				case 'select':
					groupHtml += `<select class="setting-select" data-attr="${key}">`;
					Object.entries(attrDef.options || {}).forEach(([optVal, optLabel]) => {
						groupHtml += `<option value="${optVal}" ${value === optVal ? 'selected' : ''}>${optLabel}</option>`;
					});
					groupHtml += '</select>';
					break;

				case 'color':
					const safeColor = isValidHexColor(value) ? value : '#000000';
					groupHtml += `<div class="color-input-wrap" data-color-attr="${key}">
						<input type="color" class="setting-color-picker" data-attr="${key}" data-color-role="picker" value="${safeColor}">
						<input type="text" class="setting-color-text" data-attr="${key}" data-color-role="text" value="${escapeHtml(value || '')}" placeholder="inherit" maxlength="7">
					</div>`;
					break;

				case 'toggle':
					groupHtml += `<label class="setting-toggle">
						<input type="checkbox" data-attr="${key}" ${value ? 'checked' : ''}>
						<span class="toggle-slider"></span>
					</label>`;
					break;

				case 'image':
					groupHtml += `<div class="image-input-wrap">
						<div class="image-preview" data-attr="${key}" ${value ? `style="background-image: url('${escapeHtml(value)}')"` : ''}>
							${value ? '' : '<span>No image</span>'}
						</div>
						<input type="hidden" class="setting-input" data-attr="${key}" value="${escapeHtml(value)}">
						<div class="image-buttons">
							<button type="button" class="btn-select-image" data-attr="${key}">Select Image</button>
							<button type="button" class="btn-remove-image" data-attr="${key}" ${value ? '' : 'style="display:none"'}>Remove</button>
						</div>
					</div>`;
					break;
			}

			groupHtml += '</div>';
			return groupHtml;
		};

		normalEntries.forEach(([key, attrDef]) => {
			html += renderSettingGroup(key, attrDef);
		});

		const renderSpacingGroup = (title, keys) => {
			const labels = {
				paddingTop: 'Top',
				paddingBottom: 'Bottom',
				paddingLeft: 'Left',
				paddingRight: 'Right',
				marginTop: 'Top',
				marginBottom: 'Bottom',
				marginLeft: 'Left',
				marginRight: 'Right',
			};
			let groupHtml = '';
			groupHtml += '<div class="setting-group setting-group--spacing">';
			groupHtml += `<label>${title}</label>`;
			groupHtml += '<div class="spacing-grid">';
			keys.forEach((key) => {
				const attrDef = blockDef.attributes?.[key];
				if (!attrDef || attrDef.type !== 'number') return;
				const value = attrs[key] !== undefined ? attrs[key] : attrDef.default;
				groupHtml += `<div class="spacing-field">
					<span>${labels[key] || key}</span>
					<input type="number" class="setting-input setting-input--compact" data-attr="${key}" value="${value}" min="${attrDef.min || 0}" max="${attrDef.max || 1000}">
				</div>`;
			});
			groupHtml += '</div>';
			groupHtml += '</div>';
			return groupHtml;
		};

		// Spacing controls should always be last in the settings panel.
		if (presentPadding.length) {
			html += renderSpacingGroup('Padding (px)', paddingKeys);
		}
		if (presentMargin.length) {
			html += renderSpacingGroup('Margin (px)', marginKeys);
		}

		elements.blockSettingsContent.innerHTML = html;
		initRichTextControls();

		// Add change listeners
		elements.blockSettingsContent.querySelectorAll('[data-attr]').forEach(input => {
			if (input.dataset.colorRole) return; // handled below
			input.addEventListener('change', () => {
				updateBlockAttribute(blockId, input.dataset.attr, getInputValue(input));
			});
			input.addEventListener('input', () => {
				updateBlockAttribute(blockId, input.dataset.attr, getInputValue(input));
			});
		});

		// Custom handling for color controls (supports empty/inherit)
		elements.blockSettingsContent.querySelectorAll('.color-input-wrap').forEach(wrap => {
			const attrKey = wrap.dataset.colorAttr;
			const picker = wrap.querySelector('input[type="color"]');
			const text = wrap.querySelector('input[type="text"]');
			if (!attrKey || !picker || !text) return;

			const applyValue = (nextValue) => {
				updateBlockAttribute(blockId, attrKey, nextValue);
			};

			picker.addEventListener('input', () => {
				text.value = picker.value;
				applyValue(picker.value);
			});
			picker.addEventListener('change', () => {
				text.value = picker.value;
				applyValue(picker.value);
			});

			const handleText = () => {
				const raw = (text.value || '').trim();
				if (!raw) {
					applyValue('');
					return;
				}
				if (isValidHexColor(raw)) {
					picker.value = raw;
					applyValue(raw);
				}
			};
			text.addEventListener('input', handleText);
			text.addEventListener('change', handleText);
		});

		// Add image select button listeners
		elements.blockSettingsContent.querySelectorAll('.btn-select-image').forEach(btn => {
			btn.addEventListener('click', () => {
				openMediaLibrary(blockId, btn.dataset.attr);
			});
		});

		// Add image remove button listeners
		elements.blockSettingsContent.querySelectorAll('.btn-remove-image').forEach(btn => {
			btn.addEventListener('click', () => {
				updateBlockAttribute(blockId, btn.dataset.attr, '');
				// Update UI
				const wrap = btn.closest('.image-input-wrap');
				const preview = wrap.querySelector('.image-preview');
				const hiddenInput = wrap.querySelector('input[type="hidden"]');
				preview.style.backgroundImage = '';
				preview.innerHTML = '<span>No image</span>';
				hiddenInput.value = '';
				btn.style.display = 'none';
			});
		});
	}

	/**
	 * Open WordPress Media Library
	 */
	function openMediaLibrary(blockId, attrKey) {
		// Check if wp.media is available
		if (typeof wp === 'undefined' || !wp.media) {
			alert('WordPress Media Library is not available');
			return;
		}

		const mediaFrame = wp.media({
			title: 'Select Image',
			button: { text: 'Use Image' },
			multiple: false,
			library: { type: 'image' }
		});

		mediaFrame.on('select', () => {
			const attachment = mediaFrame.state().get('selection').first().toJSON();
			const imageUrl = attachment.url;
			
			// Update block attribute
			updateBlockAttribute(blockId, attrKey, imageUrl);
			
			// Update UI
			const wrap = elements.blockSettingsContent.querySelector(`.image-input-wrap [data-attr="${attrKey}"]`)?.closest('.image-input-wrap');
			if (wrap) {
				const preview = wrap.querySelector('.image-preview');
				const hiddenInput = wrap.querySelector('input[type="hidden"]');
				const removeBtn = wrap.querySelector('.btn-remove-image');
				preview.style.backgroundImage = `url('${imageUrl}')`;
				preview.innerHTML = '';
				hiddenInput.value = imageUrl;
				removeBtn.style.display = '';
			}
		});

		mediaFrame.open();
	}

	/**
	 * Hide block settings panel
	 */
	function hideBlockSettings() {
		elements.blockSettings.style.display = 'none';
	}

	/**
	 * Show block toolbar on hover
	 */
	let toolbarTimeout = null;
	let currentToolbarBlock = null;
	
	function showBlockToolbar(blockEl, blockId) {
		if (!elements.blockToolbar) return;

		// Clear any pending hide
		if (toolbarTimeout) {
			clearTimeout(toolbarTimeout);
			toolbarTimeout = null;
		}

		currentToolbarBlock = blockId;

		const rect = blockEl.getBoundingClientRect();
		const toolbarHeight = 36;
		const gap = 8; // Gap between block and toolbar
		
		// Toggle row/column-specific buttons based on hovered element
		const blockType = blockEl.dataset.blockType;
		if (blockType === 'row') {
			elements.blockToolbar.classList.add('is-row');
		} else {
			elements.blockToolbar.classList.remove('is-row');
		}
		if (blockType === 'column') {
			elements.blockToolbar.classList.add('is-column');
		} else {
			elements.blockToolbar.classList.remove('is-column');
		}
		
		elements.blockToolbar.style.display = 'flex';
		elements.blockToolbar.dataset.blockId = blockId;

		// Measure after display
		const toolbarWidth = elements.blockToolbar.offsetWidth;
		const toolbarHeightMeasured = elements.blockToolbar.offsetHeight || toolbarHeight;

		let top = rect.top - toolbarHeightMeasured - gap;
		let left = rect.left + rect.width / 2 - toolbarWidth / 2;

		// Rows: show toolbar to the right of the row (centered vertically)
		if (blockType === 'row') {
			top = rect.top + rect.height / 2 - toolbarHeightMeasured / 2;
			left = rect.right + gap;
		}

		// Columns: show toolbar above the column (centered horizontally)
		if (blockType === 'column') {
			top = rect.top - toolbarHeightMeasured - gap;
			left = rect.left + rect.width / 2 - toolbarWidth / 2;
		}

		// Clamp to viewport
		const pad = 8;
		left = Math.max(pad, Math.min(left, window.innerWidth - toolbarWidth - pad));
		top = Math.max(pad, Math.min(top, window.innerHeight - toolbarHeightMeasured - pad));

		elements.blockToolbar.style.top = `${top}px`;
		elements.blockToolbar.style.left = `${left}px`;
	}

	/**
	 * Hide block toolbar with delay
	 */
	function hideBlockToolbar() {
		// Delay hiding so user can move mouse to toolbar
		toolbarTimeout = setTimeout(() => {
			if (elements.blockToolbar) {
				elements.blockToolbar.style.display = 'none';
			}
			currentToolbarBlock = null;
		}, 250);
	}
	
	/**
	 * Keep toolbar visible when hovering over it
	 */
	function initToolbarHover() {
		if (!elements.blockToolbar) return;
		
		elements.blockToolbar.addEventListener('mouseenter', () => {
			if (toolbarTimeout) {
				clearTimeout(toolbarTimeout);
				toolbarTimeout = null;
			}
		});
		
		elements.blockToolbar.addEventListener('mouseleave', () => {
			hideBlockToolbar();
		});
	}

	/**
	 * Initialize column resize functionality
	 */
	let resizeState = null;
	let resizeOverlayEl = null;
	let resizeRafPending = false;
	let resizeDirty = false;

	function ensureResizeOverlay() {
		if (resizeOverlayEl) return resizeOverlayEl;
		resizeOverlayEl = document.createElement('div');
		resizeOverlayEl.className = 'cm-col-resize-overlay';
		resizeOverlayEl.style.cssText = [
			'position:fixed',
			'z-index:99999',
			'pointer-events:none',
			'background:rgba(17, 24, 39, 0.92)',
			'color:#fff',
			'font-size:12px',
			'line-height:1.2',
			'padding:6px 8px',
			'border-radius:8px',
			'box-shadow:0 8px 24px rgba(0,0,0,0.25)',
			'transform:translate(-50%, -120%)',
			'display:none'
		].join(';');
		document.body.appendChild(resizeOverlayEl);
		return resizeOverlayEl;
	}

	function updateResizeOverlay(clientX, clientY, leftWidth, rightWidth) {
		const overlay = ensureResizeOverlay();
		overlay.textContent = `${Math.round(leftWidth)}% / ${Math.round(rightWidth)}%`;
		overlay.style.left = `${clientX}px`;
		overlay.style.top = `${clientY}px`;
		overlay.style.display = 'block';
	}

	function hideResizeOverlay() {
		if (!resizeOverlayEl) return;
		resizeOverlayEl.style.display = 'none';
	}

	function scheduleResizeRender() {
		if (resizeRafPending) return;
		resizeRafPending = true;
		requestAnimationFrame(() => {
			resizeRafPending = false;
			if (!resizeDirty) return;
			resizeDirty = false;
			renderBlocks();
		});
	}
	
	function initColumnResize() {
		// Handle column move buttons
		elements.canvasContent?.addEventListener('click', (e) => {
			const moveBtn = e.target.closest('.column-move-btn');
			if (!moveBtn) return;
			
			e.preventDefault();
			e.stopPropagation();
			
			const rowId = moveBtn.dataset.rowId;
			const colIndex = parseInt(moveBtn.dataset.colIndex, 10);
			const direction = moveBtn.classList.contains('move-left') ? -1 : 1;
			
			moveColumnInRow(rowId, colIndex, direction);
		});
		
		// Use event delegation on canvas for resize handles
		elements.canvasContent?.addEventListener('mousedown', (e) => {
			const handle = e.target.closest('.column-resize-handle');
			if (!handle) return;
			
			e.preventDefault();
			e.stopPropagation();
			
			const rowId = handle.dataset.rowId;
			const colIndex = parseInt(handle.dataset.colIndex, 10);
			const rowBlock = findBlock(state.template.blocks, rowId);
			
			if (!rowBlock || !rowBlock.children) return;
			
			const table = handle.closest('table');
			const tableWidth = table.offsetWidth;
			
			const leftWidth = parseFloat(rowBlock.children[colIndex].attributes?.width || 50);
			const rightWidth = parseFloat(rowBlock.children[colIndex + 1]?.attributes?.width || 50);

			resizeState = {
				rowId,
				colIndex,
				startX: e.clientX,
				tableWidth,
				leftCol: rowBlock.children[colIndex],
				rightCol: rowBlock.children[colIndex + 1],
				leftWidth,
				rightWidth,
				totalWidth: leftWidth + rightWidth
			};
			
			handle.classList.add('active');
			document.body.classList.add('is-resizing-columns');
			ensureResizeOverlay();
			updateResizeOverlay(e.clientX, e.clientY, resizeState.leftWidth, resizeState.rightWidth);
			
			document.addEventListener('mousemove', handleColumnResize);
			document.addEventListener('mouseup', stopColumnResize);
		});
	}
	
	function handleColumnResize(e) {
		if (!resizeState) return;
		
		const deltaX = e.clientX - resizeState.startX;
		const deltaPercent = (deltaX / resizeState.tableWidth) * 100;
		const total = resizeState.totalWidth;

		let newLeftWidth = resizeState.leftWidth + deltaPercent;
		let newRightWidth = total - newLeftWidth;
		
		// Minimum column width: 10%
		if (newLeftWidth < 10) {
			newLeftWidth = 10;
			newRightWidth = total - 10;
		}
		if (newRightWidth < 10) {
			newRightWidth = 10;
			newLeftWidth = total - 10;
		}

		// Final rounding for display.
		newLeftWidth = Math.round(newLeftWidth);
		newRightWidth = Math.round(newRightWidth);
		updateResizeOverlay(e.clientX, e.clientY, newLeftWidth, newRightWidth);
		
		// Update the columns visually (DOM only, not state yet)
		const rowBlock = findBlock(state.template.blocks, resizeState.rowId);
		if (rowBlock && rowBlock.children) {
			rowBlock.children[resizeState.colIndex].attributes = 
				rowBlock.children[resizeState.colIndex].attributes || {};
			rowBlock.children[resizeState.colIndex].attributes.width = newLeftWidth;
			
			if (rowBlock.children[resizeState.colIndex + 1]) {
				rowBlock.children[resizeState.colIndex + 1].attributes = 
					rowBlock.children[resizeState.colIndex + 1].attributes || {};
				rowBlock.children[resizeState.colIndex + 1].attributes.width = newRightWidth;
			}
		}

		resizeDirty = true;
		scheduleResizeRender();
	}
	
	function stopColumnResize() {
		if (resizeState) {
			document.body.classList.remove('is-resizing-columns');
			document.querySelectorAll('.column-resize-handle.active').forEach(h => h.classList.remove('active'));
			hideResizeOverlay();
			
			saveToHistory();
			markDirty();
			
			resizeState = null;
		}
		
		document.removeEventListener('mousemove', handleColumnResize);
		document.removeEventListener('mouseup', stopColumnResize);
		renderBlocks();
	}

	/**
	 * Handle block toolbar actions
	 */
	function handleBlockAction(action, blockId) {
		switch (action) {
			case 'move-up':
				moveBlock(blockId, -1);
				break;
			case 'move-down':
				moveBlock(blockId, 1);
				break;
			case 'add-column':
				addColumnToRow(blockId);
				break;
			case 'move-left': {
				const result = findBlockParentArray(state.template.blocks, blockId);
				if (result && result.parent && result.parent.type === 'row') {
					moveColumnInRow(result.parent.id, result.index, -1);
				}
				break;
			}
			case 'move-right': {
				const result = findBlockParentArray(state.template.blocks, blockId);
				if (result && result.parent && result.parent.type === 'row') {
					moveColumnInRow(result.parent.id, result.index, 1);
				}
				break;
			}
			case 'remove-column':
				removeColumn(blockId);
				break;
			case 'duplicate':
				duplicateBlock(blockId);
				break;
			case 'delete':
				if (confirm(config.i18n.confirmDelete)) {
					deleteBlock(blockId);
				}
				break;
		}
	}

	/**
	 * Remove a specific column (or fallback: last column from row)
	 */
	function removeColumn(blockId) {
		const block = findBlock(state.template.blocks, blockId);
		if (!block) return;

		// Preferred behavior: remove the targeted column.
		if (block.type === 'column') {
			const result = findBlockParentArray(state.template.blocks, blockId);
			if (!result || !result.parent || result.parent.type !== 'row') return;
			const row = result.parent;
			const cols = row.children || [];
			if (cols.length <= 1) return; // Keep at least one column
			cols.splice(result.index, 1);

			// Recalculate widths
			const newWidth = Math.floor(100 / cols.length);
			cols.forEach(col => {
				col.attributes = col.attributes || {};
				col.attributes.width = newWidth;
			});

			saveToHistory();
			markDirty();
			renderBlocks();
			return;
		}

		// Backwards-compatible fallback: if called on a row, remove the last column.
		if (block.type === 'row') {
			removeColumnFromRow(blockId);
		}
	}

	/**
	 * Add a column to a row block
	 */
	function addColumnToRow(blockId) {
		const block = findBlock(state.template.blocks, blockId);
		if (!block || block.type !== 'row') return;
		
		// Calculate new width for all columns
		const children = block.children || [];
		const newColumnCount = children.length + 1;
		const newWidth = Math.floor(100 / newColumnCount);
		
		// Update existing column widths
		children.forEach(col => {
			col.attributes = col.attributes || {};
			col.attributes.width = newWidth;
		});
		
		// Add new column
		const newColumn = {
			id: generateId(),
			type: 'column',
			attributes: { width: newWidth },
			children: []
		};
		block.children.push(newColumn);
		
		saveToHistory();
		markDirty();
		renderBlocks();
	}

	/**
	 * Remove last column from a row block
	 */
	function removeColumnFromRow(blockId) {
		const block = findBlock(state.template.blocks, blockId);
		if (!block || block.type !== 'row') return;
		
		const children = block.children || [];
		if (children.length <= 1) return; // Keep at least one column
		
		// Remove last column
		children.pop();
		
		// Recalculate widths
		const newWidth = Math.floor(100 / children.length);
		children.forEach(col => {
			col.attributes = col.attributes || {};
			col.attributes.width = newWidth;
		});
		
		saveToHistory();
		markDirty();
		renderBlocks();
	}

	/**
	 * Move a column left within a row
	 */
	function moveColumnInRow(rowId, colIndex, direction) {
		const block = findBlock(state.template.blocks, rowId);
		if (!block || block.type !== 'row') return;
		
		const children = block.children || [];
		const newIndex = colIndex + direction;
		
		if (newIndex < 0 || newIndex >= children.length) return;
		
		// Swap columns
		[children[colIndex], children[newIndex]] = [children[newIndex], children[colIndex]];
		
		saveToHistory();
		markDirty();
		renderBlocks();
	}

	/**
	 * Find block by ID recursively
	 */
	function findBlock(blocks, blockId) {
		for (const block of blocks) {
			if (block.id === blockId) return block;
			if (block.children) {
				const found = findBlock(block.children, blockId);
				if (found) return found;
			}
		}
		return null;
	}

	/**
	 * Update block attribute
	 */
	function updateBlockAttribute(blockId, attrKey, value, opts = {}) {
		const { saveHistory = true } = opts;
		const block = findBlock(state.template.blocks, blockId);
		if (!block) return;

		if (!block.attributes) block.attributes = {};
		block.attributes[attrKey] = value;

		// Re-render the specific block
		const el = document.querySelector(`[data-block-id="${blockId}"]`);
		if (el) {
			const blockDef = state.blocks[block.type];
			el.innerHTML = renderBlockContent(block, blockDef);
			
			// Also update inline styles for base style attributes
			const styleAttrs = [
				'paddingTop', 'paddingRight', 'paddingBottom', 'paddingLeft', 
				'marginTop', 'marginRight', 'marginBottom', 'marginLeft', 
				'backgroundColor', 'backgroundImage', 'backgroundRepeat', 'backgroundPosition', 'backgroundSize',
				'textColor', 'textTransform', 'fontWeight', 'fontStyle', 'textDecoration'
			];
			if (styleAttrs.includes(attrKey)) {
				el.style.cssText = buildBaseStyleCss(block.type, block.attributes || {});
			}
		}

		markDirty();
		if (saveHistory) {
			saveToHistory();
		}
	}

	/**
	 * Add a new block
	 */
	function addBlock(blockType, index = null, parentId = null) {
		const blockDef = state.blocks[blockType];
		if (!blockDef) return;

		// Create new block with defaults
		const newBlock = {
			id: generateId(),
			type: blockType,
			attributes: {},
			children: []
		};

		// Set default attributes
		Object.entries(blockDef.attributes || {}).forEach(([key, attrDef]) => {
			newBlock.attributes[key] = attrDef.default;
		});

		// Add default children if defined
		if (blockDef.default_children) {
			blockDef.default_children.forEach(childDef => {
				const childBlock = {
					id: generateId(),
					type: childDef.type,
					attributes: { ...childDef.attributes },
					children: []
				};
				newBlock.children.push(childBlock);
			});
		}

		// Add to template
		if (parentId) {
			const parent = findBlock(state.template.blocks, parentId);
			if (parent && parent.children) {
				if (index !== null) {
					parent.children.splice(index, 0, newBlock);
				} else {
					parent.children.push(newBlock);
				}
			}
		} else {
			if (index !== null) {
				state.template.blocks.splice(index, 0, newBlock);
			} else {
				state.template.blocks.push(newBlock);
			}
		}

		renderBlocks();
		selectBlock(newBlock.id);
		markDirty();
		saveToHistory();

		return newBlock.id;
	}

	/**
	 * Delete a block
	 */
	function deleteBlock(blockId) {
		state.template.blocks = removeBlock(state.template.blocks, blockId);
		renderBlocks();
		deselectBlock();
		markDirty();
		saveToHistory();
	}

	/**
	 * Remove block from array recursively
	 */
	function removeBlock(blocks, blockId) {
		return blocks.filter(block => {
			if (block.id === blockId) return false;
			if (block.children) {
				block.children = removeBlock(block.children, blockId);
			}
			return true;
		});
	}

	/**
	 * Duplicate a block (works with nested blocks)
	 */
	function duplicateBlock(blockId) {
		const result = findBlockParentArray(state.template.blocks, blockId);
		if (!result) return;
		
		const { array, index } = result;
		const original = array[index];
		const copy = JSON.parse(JSON.stringify(original));
		
		// Generate new IDs recursively
		function regenerateIds(block) {
			block.id = generateId();
			if (block.children) {
				block.children.forEach(child => regenerateIds(child));
			}
		}
		regenerateIds(copy);

		array.splice(index + 1, 0, copy);
		renderBlocks();
		selectBlock(copy.id);
		markDirty();
		saveToHistory();
	}

	/**
	 * Find the parent array containing a block (for nested blocks)
	 */
	function findBlockParentArray(blocks, blockId, parent = null) {
		for (let i = 0; i < blocks.length; i++) {
			if (blocks[i].id === blockId) {
				return { array: blocks, index: i, parent };
			}
			if (blocks[i].children) {
				const result = findBlockParentArray(blocks[i].children, blockId, blocks[i]);
				if (result) return result;
			}
		}
		return null;
	}

	/**
	 * Move a block up or down (works with nested blocks)
	 */
	function moveBlock(blockId, direction) {
		const result = findBlockParentArray(state.template.blocks, blockId);
		if (!result) return;
		
		const { array, index } = result;
		const newIndex = index + direction;
		
		if (newIndex < 0 || newIndex >= array.length) return;

		// Swap
		[array[index], array[newIndex]] = [array[newIndex], array[index]];
		
		renderBlocks();
		markDirty();
		saveToHistory();
	}

	// ========================================
	// Drag & Drop Handlers
	// ========================================

	let currentDropTarget = null;
	let dropPosition = null; // 'before', 'after', 'inside'

	function handleDragStart(e) {
		e.dataTransfer.effectAllowed = 'copy';
		e.dataTransfer.setData('text/plain', e.target.dataset.blockType);
		e.dataTransfer.setData('application/x-block-type', e.target.dataset.blockType);
		e.target.classList.add('dragging');
		
		// Add dragging class to body for global styling
		document.body.classList.add('is-dragging');
	}

	function handleBlockDragStart(e, blockId) {
		e.dataTransfer.effectAllowed = 'move';
		e.dataTransfer.setData('text/plain', `move:${blockId}`);
		e.target.classList.add('dragging');
		document.body.classList.add('is-dragging');
	}

	function handleDragEnd(e) {
		e.target.classList.remove('dragging');
		document.body.classList.remove('is-dragging');
		clearAllDropHighlights();
		hideDropIndicator();
	}

	function handleDragOver(e) {
		e.preventDefault();
		e.dataTransfer.dropEffect = 'copy';
		
		// Check if we're over a column placeholder (drop zone)
		const columnPlaceholder = e.target.closest('.column-placeholder');
		if (columnPlaceholder) {
			clearAllDropHighlights();
			columnPlaceholder.classList.add('drop-highlight');
			currentDropTarget = columnPlaceholder;
			dropPosition = 'inside';
			hideDropIndicator();
			return;
		}
		
		// Check if over empty canvas
		const emptyCanvas = e.target.closest('#canvas-empty');
		if (emptyCanvas) {
			clearAllDropHighlights();
			emptyCanvas.classList.add('drop-highlight');
			currentDropTarget = emptyCanvas;
			dropPosition = 'inside';
			hideDropIndicator();
			return;
		}
		
		// Show drop indicator for blocks
		const target = e.target.closest('.template-block');
		if (target && !target.closest('.column-placeholder')) {
			clearAllDropHighlights();
			currentDropTarget = target;
			showDropIndicator(target, e.clientY, e.clientX);
		}
	}

	function handleDragLeave(e) {
		const relatedTarget = e.relatedTarget;
		
		// Clear highlight if leaving drop zone
		if (currentDropTarget && !currentDropTarget.contains(relatedTarget)) {
			currentDropTarget.classList.remove('drop-highlight');
		}
		
		if (!elements.canvasContent.contains(relatedTarget)) {
			clearAllDropHighlights();
			hideDropIndicator();
		}
	}

	function handleDrop(e) {
		e.preventDefault();
		e.stopPropagation();
		
		clearAllDropHighlights();
		hideDropIndicator();
		document.body.classList.remove('is-dragging');

		const data = e.dataTransfer.getData('text/plain');
		
		if (data.startsWith('move:')) {
			// Moving existing block
			const blockId = data.replace('move:', '');
			// TODO: Implement reordering
			return;
		}
		
		// Adding new block
		const blockType = data;
		if (!blockType || !state.blocks[blockType]) {
			console.warn('Invalid block type:', blockType);
			return;
		}

		// Check if dropping into a column placeholder
		const columnPlaceholder = e.target.closest('.column-placeholder');
		if (columnPlaceholder) {
			const columnId = columnPlaceholder.dataset.columnId;
			if (columnId) {
				addBlockToColumn(blockType, columnId);
				return;
			}
			
			// Fallback: try to find column from parent td
			const columnCell = columnPlaceholder.closest('td[data-column-id]');
			if (columnCell) {
				addBlockToColumn(blockType, columnCell.dataset.columnId);
				return;
			}
		}
		
		// Check if dropping on empty canvas
		if (e.target.closest('#canvas-empty')) {
			addBlock(blockType, 0);
			return;
		}

		// If dropping anywhere in a column cell (even non-empty), prefer inserting into that column.
		// Precise positioning inside a column is handled when dropping on a specific child block.
		const columnCell = e.target.closest('td[data-column-id]');
		if (columnCell && columnCell.dataset.columnId) {
			// If we can resolve an exact child block target, we'll do it below; otherwise append to column.
			const possibleChild = e.target.closest('.template-block[data-block-id]');
			if (!possibleChild || possibleChild === columnCell) {
				addBlockToColumn(blockType, columnCell.dataset.columnId);
				return;
			}
		}

		// Regular block drop - use stored currentDropTarget and dropPosition from dragOver
		let target = currentDropTarget;
		if (!target || !target.classList.contains('template-block')) {
			// Fallback: try to find target from event
			target = e.target.closest('.template-block');
		}

		let index = null;
		let parentId = null;
		if (target && target.classList.contains('template-block')) {
			const targetId = target.dataset.blockId;
			const result = targetId ? findBlockParentArray(state.template.blocks, targetId) : null;
			if (result) {
				parentId = result.parent?.id || null;
				const targetIndex = result.index;
				// Use stored dropPosition if available, otherwise calculate
				if (dropPosition === 'before') {
					index = targetIndex;
				} else if (dropPosition === 'after') {
					index = targetIndex + 1;
				} else {
					const rect = target.getBoundingClientRect();
					index = e.clientY < rect.top + rect.height / 2 ? targetIndex : targetIndex + 1;
				}
			}
		}

		addBlock(blockType, index, parentId);
	}
	
	/**
	 * Add block inside a column
	 */
	function addBlockToColumn(blockType, columnId) {
		const blockDef = state.blocks[blockType];
		if (!blockDef) return;

		// Create new block
		const newBlock = {
			id: generateId(),
			type: blockType,
			attributes: {},
			children: []
		};

		// Set default attributes
		Object.entries(blockDef.attributes || {}).forEach(([key, attrDef]) => {
			newBlock.attributes[key] = attrDef.default;
		});

		// Find the column and add the block
		function addToColumn(blocks) {
			for (const block of blocks) {
				if (block.id === columnId) {
					if (!block.children) block.children = [];
					block.children.push(newBlock);
					return true;
				}
				if (block.children && addToColumn(block.children)) {
					return true;
				}
			}
			return false;
		}

		if (addToColumn(state.template.blocks)) {
			renderBlocks();
			selectBlock(newBlock.id);
			markDirty();
			saveToHistory();
		}
	}
	
	/**
	 * Clear all drop highlights
	 */
	function clearAllDropHighlights() {
		document.querySelectorAll('.drop-highlight').forEach(el => {
			el.classList.remove('drop-highlight');
		});
		currentDropTarget = null;
		dropPosition = null;
	}

	function showDropIndicator(target, mouseY, mouseX) {
		if (!elements.dropIndicator) return;

		const rect = target.getBoundingClientRect();
		const wrapRect = elements.wrap?.getBoundingClientRect?.() || { left: 0, top: 0 };
		const isAbove = mouseY < rect.top + rect.height / 2;
		const offset = 6; // keep indicator from overlapping the block

		elements.dropIndicator.style.display = 'block';
		elements.dropIndicator.style.width = `${rect.width}px`;
		elements.dropIndicator.style.left = `${rect.left - wrapRect.left}px`;
		elements.dropIndicator.style.top = isAbove
			? `${rect.top - wrapRect.top - offset}px`
			: `${rect.bottom - wrapRect.top + offset}px`;
		elements.dropIndicator.classList.add('visible');
		
		dropPosition = isAbove ? 'before' : 'after';
	}

	function hideDropIndicator() {
		if (elements.dropIndicator) {
			elements.dropIndicator.classList.remove('visible');
			elements.dropIndicator.style.display = 'none';
		}
	}

	// ========================================
	// View & Zoom
	// ========================================

	function switchView(view) {
		state.view = view;
		
		document.querySelectorAll('.view-btn').forEach(btn => {
			btn.classList.toggle('active', btn.dataset.view === view);
		});

		if (view === 'preview') {
			elements.previewPanel.style.display = 'flex';
			elements.previewPanel.style.width = '100%';
			if (elements.canvasWrap) {
				elements.canvasWrap.style.display = 'none';
			}
			refreshPreview();
		} else if (view === 'split') {
			elements.previewPanel.style.display = 'flex';
			elements.previewPanel.style.width = '50%';
			elements.previewPanel.style.flex = 'auto';
			if (elements.canvasWrap) {
				elements.canvasWrap.style.display = 'flex';
				elements.canvasWrap.style.width = '50%';
				elements.canvasWrap.style.flex = 'auto';
			}
			refreshPreview();
		} else {
			elements.previewPanel.style.display = 'none';
			if (elements.canvasWrap) {
				elements.canvasWrap.style.display = 'flex';
				elements.canvasWrap.style.width = '';
				elements.canvasWrap.style.flex = '';
			}
		}
	}

	function setZoom(level) {
		level = Math.max(25, Math.min(200, level));
		state.zoom = level;
		
		elements.pdfCanvas.style.transform = `scale(${level / 100})`;
		elements.zoomLevel.textContent = `${level}%`;
	}

	function zoomToFit() {
		const container = elements.canvasContainer;
		const canvas = elements.pdfCanvas;
		
		const containerRect = container.getBoundingClientRect();
		const canvasWidth = parseFloat(getComputedStyle(canvas).width);
		const canvasHeight = parseFloat(getComputedStyle(canvas).height);
		
		const padding = 80;
		const scaleX = (containerRect.width - padding) / canvasWidth;
		const scaleY = (containerRect.height - padding) / canvasHeight;
		const scale = Math.min(scaleX, scaleY, 1) * 100;
		
		setZoom(Math.round(scale));
	}

	// ========================================
	// Canvas Updates
	// ========================================

	function updateCanvasDimensions() {
		const dimensions = getPaperDimensions();
		elements.pdfCanvas.style.setProperty('--paper-width', `${dimensions.width}mm`);
		elements.pdfCanvas.style.setProperty('--paper-height', `${dimensions.height}mm`);
		
		// Update page info display
		const pageInfo = document.querySelector('.page-info .paper-size');
		if (pageInfo) {
			pageInfo.textContent = config.paperSizes[state.template.page_settings.paperSize] || 'A4';
		}
		const orientationInfo = document.querySelector('.page-info .orientation');
		if (orientationInfo) {
			orientationInfo.textContent = state.template.page_settings.orientation.charAt(0).toUpperCase() + 
				state.template.page_settings.orientation.slice(1);
		}
	}

	function updateCanvasMargins() {
		const ps = state.template.page_settings;
		elements.pdfCanvas.style.setProperty('--margin-top', `${ps.marginTop}mm`);
		elements.pdfCanvas.style.setProperty('--margin-right', `${ps.marginRight}mm`);
		elements.pdfCanvas.style.setProperty('--margin-bottom', `${ps.marginBottom}mm`);
		elements.pdfCanvas.style.setProperty('--margin-left', `${ps.marginLeft}mm`);
	}

	function updateCanvasStyles() {
		const ps = state.template.page_settings;
		elements.pdfCanvas.style.setProperty('--base-font-size', `${ps.baseFontSize}pt`);
		elements.pdfCanvas.style.setProperty('--text-color', ps.textColor);
		elements.pdfCanvas.style.setProperty('--bg-color', ps.backgroundColor);
		
		// Background image
		if (ps.backgroundImage) {
			elements.pdfCanvas.style.backgroundImage = `url('${ps.backgroundImage}')`;
			elements.pdfCanvas.style.backgroundRepeat = ps.backgroundRepeat || 'no-repeat';
			elements.pdfCanvas.style.backgroundPosition = ps.backgroundPosition || 'top left';
			elements.pdfCanvas.style.backgroundSize = ps.backgroundSize || 'auto';
		} else {
			elements.pdfCanvas.style.backgroundImage = 'none';
		}
	}

	function getPaperDimensions() {
		const sizes = {
			'a4': [210, 297],
			'letter': [216, 279],
			'legal': [216, 356],
			'a3': [297, 420],
			'a5': [148, 210]
		};
		
		const ps = state.template.page_settings;
		let [width, height] = sizes[ps.paperSize] || sizes.a4;
		
		if (ps.orientation === 'landscape') {
			[width, height] = [height, width];
		}
		
		return { width, height };
	}

	// ========================================
	// History (Undo/Redo)
	// ========================================

	function saveToHistory() {
		// Remove any future history if we're not at the end
		if (state.historyIndex < state.history.length - 1) {
			state.history = state.history.slice(0, state.historyIndex + 1);
		}

		// Add current state
		state.history.push(JSON.stringify(state.template));
		
		// Limit history size
		if (state.history.length > state.maxHistory) {
			state.history.shift();
		}
		
		state.historyIndex = state.history.length - 1;
		updateHistoryButtons();
	}

	function undo() {
		if (state.historyIndex > 0) {
			state.historyIndex--;
			state.template = JSON.parse(state.history[state.historyIndex]);
			renderBlocks();
			updateHistoryButtons();
			markDirty();
		}
	}

	function redo() {
		if (state.historyIndex < state.history.length - 1) {
			state.historyIndex++;
			state.template = JSON.parse(state.history[state.historyIndex]);
			renderBlocks();
			updateHistoryButtons();
			markDirty();
		}
	}

	function updateHistoryButtons() {
		if (elements.btnUndo) {
			elements.btnUndo.disabled = state.historyIndex <= 0;
		}
		if (elements.btnRedo) {
			elements.btnRedo.disabled = state.historyIndex >= state.history.length - 1;
		}
	}

	// ========================================
	// Save & Preview
	// ========================================

	function markDirty() {
		state.isDirty = true;
	}

	function handleBeforeUnload(e) {
		if (state.isDirty) {
			e.preventDefault();
			e.returnValue = config.i18n.unsavedChanges;
			return config.i18n.unsavedChanges;
		}
	}

	async function saveTemplate() {
		const btn = document.getElementById('btn-save-template');
		const originalText = btn.innerHTML;
		
		console.log('Saving template:', state.template);
		const templateToSave = normalizeTemplateForSave(state.template);
		
		btn.disabled = true;
		btn.innerHTML = '<svg class="spin" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg> Saving...';

		try {
			const response = await fetch(config.ajaxUrl, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded',
				},
				body: new URLSearchParams({
					action: 'checkmate_save_template',
					nonce: config.nonce,
					template: JSON.stringify(templateToSave)
				})
			});

			const result = await response.json();
			console.log('Save response:', result);

			if (result.success) {
				state.isDirty = false;
				if (result.data.id && !state.template.id) {
					state.template.id = result.data.id;
					// Update URL with new ID
					const url = new URL(window.location.href);
					url.searchParams.set('template_id', result.data.id);
					window.history.replaceState({}, '', url);
				}
				showToast(config.i18n?.saved || 'Template saved!', 'success');
				// Show checkmark briefly
				btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg> Saved';
				btn.classList.add('saved');
				setTimeout(() => {
					btn.innerHTML = originalText;
					btn.classList.remove('saved');
					btn.disabled = false;
				}, 2000);
			} else {
				showToast(result.data?.message || config.i18n?.saveError || 'Error saving template', 'error');
			}
		} catch (error) {
			console.error('Save error:', error);
			showToast(config.i18n?.saveError || 'Error saving template', 'error');
		}

		btn.disabled = false;
		btn.innerHTML = originalText;
	}

	function refreshPreview() {
		if (!elements.previewIframe) {
			console.error('Preview iframe not found');
			return;
		}

		console.log('Refreshing preview with template:', state.template);
		console.log('Template blocks:', state.template.blocks);

		// Use fetch to get HTML and inject into iframe
		fetch(config.ajaxUrl, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded',
			},
			body: new URLSearchParams({
				action: 'checkmate_preview_pdf',
				nonce: config.nonce,
					template: JSON.stringify(normalizeTemplateForSave(state.template))
			})
		})
		.then(response => response.text())
		.then(html => {
			console.log('Preview HTML received, length:', html.length);
			const iframeDoc = elements.previewIframe.contentDocument || elements.previewIframe.contentWindow.document;
			iframeDoc.open();
			iframeDoc.write(html);
			iframeDoc.close();
		})
		.catch(error => {
			console.error('Preview error:', error);
		});
	}

	async function downloadPDF() {
		const btn = document.getElementById('btn-download-pdf');
		const originalText = btn.innerHTML;
		
		btn.disabled = true;
		btn.innerHTML = '<svg class="spin" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg> Generating...';

		try {
			const response = await fetch(config.ajaxUrl, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded',
				},
				body: new URLSearchParams({
					action: 'checkmate_generate_pdf',
					nonce: config.nonce,
					template: JSON.stringify(normalizeTemplateForSave(state.template))
				})
			});

			if (response.ok) {
				const blob = await response.blob();
				const url = window.URL.createObjectURL(blob);
				const a = document.createElement('a');
				a.href = url;
				a.download = `${state.template.name || 'template'}.pdf`;
				document.body.appendChild(a);
				a.click();
				window.URL.revokeObjectURL(url);
				document.body.removeChild(a);
			} else {
				let message = `PDF generation failed (HTTP ${response.status})`;
				try {
					const contentType = response.headers.get('content-type') || '';
					if (contentType.includes('application/json')) {
						const payload = await response.json();
						message = payload?.data?.message || payload?.message || message;
					} else {
						const text = await response.text();
						if (text && text.trim()) {
							message = text.replace(/<[^>]*>/g, '').trim();
						}
					}
				} catch (e) {
					// ignore parsing errors
				}
				throw new Error(message);
			}
		} catch (error) {
			console.error('PDF generation error:', error);
			showToast(error?.message || 'Failed to generate PDF', 'error');
		}

		btn.disabled = false;
		btn.innerHTML = originalText;
	}

	// ========================================
	// Utility Functions
	// ========================================

	function generateId() {
		return 'block_' + Math.random().toString(36).substr(2, 9);
	}

	function escapeHtml(str) {
		const div = document.createElement('div');
		div.textContent = str;
		return div.innerHTML;
	}

	function isValidHexColor(value) {
		return typeof value === 'string' && /^#[0-9a-fA-F]{6}$/.test(value);
	}

	function getInputValue(input) {
		if (input.type === 'checkbox') {
			return input.checked;
		}
		if (input.type === 'number') {
			return parseFloat(input.value) || 0;
		}
		return input.value;
	}

	function filterBlocks(e) {
		const query = e.target.value.toLowerCase();
		document.querySelectorAll('.block-item').forEach(item => {
			const title = item.querySelector('.block-title').textContent.toLowerCase();
			item.style.display = title.includes(query) ? 'flex' : 'none';
		});
	}

	function handleKeyboard(e) {
		// Undo: Ctrl/Cmd + Z
		if ((e.ctrlKey || e.metaKey) && e.key === 'z' && !e.shiftKey) {
			e.preventDefault();
			undo();
		}
		// Redo: Ctrl/Cmd + Shift + Z or Ctrl/Cmd + Y
		if ((e.ctrlKey || e.metaKey) && ((e.key === 'z' && e.shiftKey) || e.key === 'y')) {
			e.preventDefault();
			redo();
		}
		// Save: Ctrl/Cmd + S
		if ((e.ctrlKey || e.metaKey) && e.key === 's') {
			e.preventDefault();
			saveTemplate();
		}
		// Delete selected block: Delete or Backspace
		if ((e.key === 'Delete' || e.key === 'Backspace') && state.selectedBlock) {
			const active = document.activeElement;
			if (active.tagName !== 'INPUT' && active.tagName !== 'TEXTAREA') {
				e.preventDefault();
				if (confirm(config.i18n.confirmDelete)) {
					deleteBlock(state.selectedBlock);
				}
			}
		}
		// Escape: Deselect
		if (e.key === 'Escape') {
			deselectBlock();
		}
	}

	function showToast(message, type = 'info') {
		const toast = document.createElement('div');
		toast.className = `editor-toast toast-${type}`;
		toast.textContent = message;
		toast.style.cssText = `
			position: fixed;
			bottom: 20px;
			right: 20px;
			padding: 12px 20px;
			background: ${type === 'success' ? '#34c759' : type === 'error' ? '#ff3b30' : '#007aff'};
			color: white;
			border-radius: 8px;
			font-size: 14px;
			font-weight: 500;
			box-shadow: 0 4px 12px rgba(0,0,0,0.2);
			z-index: 999999;
			animation: slideIn 0.3s ease;
			pointer-events: none;
		`;

		// Append to editor wrap for proper stacking context
		const editorWrap = document.querySelector('.checkmate-editor-wrap');
		(editorWrap || document.body).appendChild(toast);

		setTimeout(() => {
			toast.style.animation = 'slideOut 0.3s ease';
			setTimeout(() => toast.remove(), 300);
		}, 3000);
	}

	// Add CSS animations
	const style = document.createElement('style');
	style.textContent = `
		@keyframes slideIn {
			from { transform: translateX(100%); opacity: 0; }
			to { transform: translateX(0); opacity: 1; }
		}
		@keyframes slideOut {
			from { transform: translateX(0); opacity: 1; }
			to { transform: translateX(100%); opacity: 0; }
		}
		@keyframes spin {
			to { transform: rotate(360deg); }
		}
		.spin { animation: spin 1s linear infinite; }
	`;
	document.head.appendChild(style);

})();
