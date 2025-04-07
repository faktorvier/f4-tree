import { Wunderbaum } from 'https://cdn.jsdelivr.net/npm/wunderbaum@0.13/+esm';

class Tree {
	wunderbaumOptions = {
		id: 'f4-tree',
		element: '.f4-tree__inner',
		debugLevel: 0,
		dnd: {
			autoExpandMS: 800,
			effectAllowed: 'move',
			dropEffectDefault: 'move',
			guessDropEffect: true,
			dragStart: this.onDragStart.bind(this),
			dragEnter: this.onDragEnter.bind(this),
			drop: this.onDragDrop.bind(this)
		},
		init: this.onInit.bind(this),
		render: this.onRender.bind(this),
		beforeActivate: this.onBeforeActivate.bind(this),
		beforeExpand: this.onBeforeExpand.bind(this),
		expand: this.onExpand.bind(this)
	}

	wpwrap = null;
	tree = null;
	wunderbaum = null;
	pollingTimeout = null;
	expandedNodes = new Set();
	resizeTimeout = null;

	/**
	 * Constructor.
	 */
	constructor() {
		this.wpwrap = document.querySelector('#wpwrap');
		this.tree = document.querySelector('.f4-tree');

		const sessionExpandedNodes = sessionStorage.getItem('f4-tree-expanded-nodes');

		if(sessionExpandedNodes) {
			this.expandedNodes = new Set(JSON.parse(sessionExpandedNodes));
		}

		let options = Object.assign(
			{},
			this.wunderbaumOptions,
			F4TREE.tree ?? {}
		);

		if(!F4TREE.isSortable) {
			options.dnd = null;
		}

		// options = options.iconMap = Object.assign(
		// 	Wunderbaum.iconMaps["bootstrap"],
		// 	{
		// 	folder: "bi bi-file-earmark",
		// 	},
		// );

		this.wunderbaum = new Wunderbaum(
			options
		);

		this.bindResize();
	}

	/**
	 * Start the polling for tree changes.
	 */
	startPolling() {
		this.stopPolling();

		this.pollingTimeout = setTimeout(() => {
			jQuery.ajax({
				url: F4TREE.pollingFile,
				dataType: 'json',
				cache: false,
				success: (latestChange) => {
					if(latestChange > F4TREE.lastChange) {
						F4TREE.lastChange = latestChange;

						this.loadTree((data) => {
							// this.getExpandedNodes(
							// 	data.tree
							// );

							this.applyExpandedNodes(
								data.tree
							);

							this.updatePostEdit({
								samplePermalink: data.samplePermalink,
								menuOrder: data.menuOrder
							});
						});
					} else {
						this.startPolling();
					}
				},
				error: () => {
					this.startPolling();
				}
			});
		}, F4TREE.pollingTimeout);
	}

	/**
	 * Stop the polling for tree changes.
	 */
	stopPolling() {
		clearTimeout(this.pollingTimeout);
	}

	/**
	 * Load the tree from database.
	 */
	loadTree(callback = null) {
		jQuery.ajax({
			url: ajaxurl,
			dataType: 'json',
			cache: false,
			data: {
				action: 'f4_tree_refresh',
				post_id: F4TREE.postId,
				post_type: F4TREE.postType,
				lang: F4TREE.lang
			},
			success: (data) => {
				if(typeof callback === 'function') {
					callback(data);
				}

				this.refreshTree(
					data.tree
				);
			},
			complete: () => {
				this.startPolling();
			}
		});
	}

	/**
	 * Refresh the tree with new data.
	 */
	refreshTree(data) {
		this.wunderbaum.load(
			data
		);
	}

	/**
	 * Get an object with all the sorted pages.
	 */
	getSortedNodes() {
		let nodes = {
			menus: {},
			posts: []
		};

		this.wunderbaum.visit(node => {
			if(node.parent.type === 'menu') {
				nodes.menus[node.parent.data.menu_id] ??= [];

				nodes.menus[node.parent.data.menu_id].push({
					title: node.title,
					post_id: node.data.post_id ?? 0,
					menu_item_id: node.data.menu_item_id ?? 0
				});
			}

			if(node.type === 'post') {
				nodes.posts.push({
					type: node.type,
					post_id: node.data?.post_id ?? 0,
					parent_post_id: node.parent.data?.post_id ?? 0
				});
			}
		});

		return nodes;
	}

	/**
	 * Update post edit fields for the current edit page.
	 */
	updatePostEdit(args) {
		args.samplePermalink = args.samplePermalink ?? false;
		args.menuOrder = args.menuOrder ?? -1;

		// Sample permalink
		if(args.samplePermalink) {
			if(jQuery('#sample-permalink a').length) {
				jQuery('#edit-slug-box').html(
					args.samplePermalink
				);
			} else if(jQuery('#sample-permalink').length) {
				let currentSlug = jQuery('#new-post-slug').val();

				jQuery('#edit-slug-box').html(
					args.samplePermalink
				);

				requestAnimationFrame(() => {
					jQuery('#titlediv .edit-slug').trigger('click');
					jQuery('#new-post-slug').val(currentSlug);
				});
			}
		}

		// Menu order
		if(args.menuOrder > -1) {
			jQuery('#menu_order').val(args.menuOrder);
		}
	}

	/**
	 * Get all expanded nodes.
	 */
	getExpandedNodes(nodes) {
		nodes.forEach(node => {
			if(node.expanded) {
				this.expandedNodes.add(node.key);

				sessionStorage.setItem(
					'f4-tree-expanded-nodes',
					JSON.stringify(Array.from(this.expandedNodes))
				);
			}

			if(node.children && node.children.length) {
				this.getExpandedNodes(
					node.children
				);
			}
		});
	}

	/**
	 * Apply expanded nodes.
	 */
	applyExpandedNodes(nodes) {
		nodes.forEach(node => {
			node.expanded = this.expandedNodes.has(
				node.key
			);

			if(node.children && node.children.length) {
				this.applyExpandedNodes(
					node.children
				);
			}
		});
	}

	/**
	 * Wunderbaum event when the tree markup is created and the initial source was loaded.
	 */
	onInit(e) {
		this.loadTree((data) => {
			this.getExpandedNodes(
				data.tree
			);

			this.applyExpandedNodes(
				data.tree
			);
		});

		document.addEventListener('visibilitychange', () => {
			if(document.visibilityState === 'visible') {
				this.startPolling();
			} else {
				this.stopPolling();
			}
		});
	}

	/**
	 * Wunderbaum event when a node is about to be displayed.
	 */
	onRender(e) {
		if(typeof e.node.data.url !== 'undefined') {
			e.nodeElem.querySelector('.wb-title').innerHTML = `<a class="wb-link" href="${e.node.data.url}" draggable="false">${e.node.title}</a>`;
		}

		if(e.node.data.active) {
			e.nodeElem.parentNode.classList.add('wb-row--active');
		}

		if(F4TREE.iconMap[e.node.icon]) {
			let icon = F4TREE.iconMap[e.node.icon];

			if(icon.startsWith('<') && !icon.startsWith('<i')) {
				icon = `<i class="wb-icon wb-icon--${e.node.icon}">${icon}</i>`;
			}

			e.node.setIcon(icon);
		}
	}

	/**
	 * Wunderbaum event before a node is activated. Return false to prevent activation.
	 */
	onBeforeActivate(e) {
		//return !e.node.unselectable;
		return false;
	}

	/**
	 * Wunderbaum event before a node is expanded or collapsed. Return false to prevent expand/collapsing.
	 */
	onBeforeExpand(e) {
		return e.node.children.length > 0;
	}

	/**
	 * Wunderbaum event when a node is expanded or collapsed (e.flag = true/false).
	 */
	onExpand(e) {
		if(e.flag) {
			this.expandedNodes.add(
				e.node.key
			);
		} else {
			this.expandedNodes.delete(
				e.node.key
			);
		}

		sessionStorage.setItem(
			'f4-tree-expanded-nodes',
			JSON.stringify(Array.from(this.expandedNodes))
		);
	}

	/**
	 * Wunderbaum event when drag & drop operation is started.
	 */
	onDragStart(e) {
		if(e.node.data.draggable ?? true) {
			this.stopPolling();
			return true;
		}

		return false;
	}

	/**
	 * Wunderbaum event when drag & drop item enters a drop target.
	 */
	onDragEnter(e) {
		const target = e.node;
		const source = e.sourceNode;

		// Disable dnd into level 1
		if(target.getLevel() === 1 && (source.type === 'post' || source.type !== 'pool')) {
			return ['over'];
		}

		// Disable dnd for non-post-nodes into pool
		if(source.type !== 'post' && target.parent.type === 'pool') {
			return false;
		}

		// Disable dnd for same posts into each other
		if(source.type === 'post' && source.data.post_id === target.data.post_id) {
			return false;
		}

		// Disable dnd for non-post-nodes for all levels except 2
		if(source.type !== 'post' && target.getLevel() !== 2) {
			return false;
		}

		// Disable nesting for non-post-nodes
		if(source.type !== 'post' || target.type !== 'post') {
			return ['before', 'after'];
		}

		// Disable dnd into non-hierarchival nodes
		if(!target.data.allow_children || !source.data.allow_children) {
			return ['before', 'after'];
		}

		// Allow all other dnd's
		return true;
	}

	/**
	 * Wunderbaum event when drag & drop item is droppet in a drop target.
	 */
	onDragDrop(e) {
		e.sourceNode.moveTo(
			e.node,
			e.suggestedDropMode
		);

		e.node.setExpanded(
			true
		);

		let sortedNodes = this.getSortedNodes();

		jQuery.ajax({
			url: ajaxurl,
			dataType: 'json',
			type: 'POST',
			cache: false,
			data: {
				action: 'f4_tree_move_post',
				post_id: F4TREE.postId,
				post_type: F4TREE.postType,
				lang: F4TREE.lang,
				menus_sorted: JSON.stringify(sortedNodes.menus),
				posts_sorted: JSON.stringify(sortedNodes.posts)
			},
			success: (data) => {
				F4TREE.lastChange = data.latestChange;

				this.updatePostEdit({
					samplePermalink: data.samplePermalink,
					menuOrder: data.menuOrder
				});

				this.startPolling();
			}
		});
	}

	/**
	 * Observe tree resize.
	 */
	bindResize() {
		const observer = new MutationObserver((mutations) => {
			mutations.forEach((mutation) => {
				if(mutation.attributeName === 'style') {
					clearTimeout(
						this.resizeTimeout
					);

					this.wpwrap.style.setProperty(
						'--f4-tree-container-width',
						this.tree.style.width
					);

					this.resizeTimeout = setTimeout(() => {
						jQuery.ajax({
							url: ajaxurl,
							cache: false,
							data: {
								action: 'f4_tree_update_width',
								width: this.tree.style.width
							}
						});
					}, 1000);
				}
			});
		});

		observer.observe(this.tree, {
			attributes: true,
			attributeFilter: ['style']
		});

		// Reset on doubleclick
		this.tree.addEventListener('dblclick', (event) => {
			const rect = this.tree.getBoundingClientRect();
			const padding = 20;

			if(event.clientX >= rect.right - padding && event.clientX <= rect.right + padding) {
				this.wpwrap.style.setProperty(
					'--f4-tree-container-width',
					''
				);

				document.querySelector('#f4-tree-container-width-style').remove();

				jQuery.ajax({
					url: ajaxurl,
					cache: false,
					data: {
						action: 'f4_tree_update_width',
						width: ''
					}
				});
			}
		});
	}
}

(function($) {
	new Tree();
})(jQuery);
