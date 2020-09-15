var F4_menutree = {
	containerSelector: '#f4-tree-inner',
	nodeItemPrefix: 'tree-item-',

	dndAutoExpandDelay: 800,

	tree: null,
	$container: null,
	currentPostId: -1,
	currentPostType: '',
	currentLanguage: '',
	pollXHR: null,
	pollErrorCount: 0,
	pollLastChange: null,
	isDnd: false
};

(function($) {
	// Methods
	F4_menutree.init = function() {
		F4_menutree.$container = jQuery(F4_menutree.containerSelector);
		F4_menutree.currentPostId = jQuery('#post_ID').length ? jQuery('#post_ID').val() : -1;
		F4_menutree.pollLastChange = F4_menutree.$container.attr('data-tree-lastchange');
		F4_menutree.currentPostType = F4_menutree.$container.attr('data-tree-post-type');
		F4_menutree.currentLanguage = F4_menutree.$container.attr('data-tree-language');

		var fancytreeConfig = {
			activeVisible: true,
			autoActivate: false,
			autoCollapse: false,
			autoScroll: true,
			toggleEffect: false,
			checkbox: false,
			clickFolderMode: 3,
			debugLevel: 0,
			generateIds: true,
			idPrefix: F4_menutree.nodeItemPrefix,

			init: function() {
				F4_menutree.tree = F4_menutree.$container.fancytree('getTree');
				F4_menutree.startPoll();
			},

			// Translations
			strings: {
				loading: F4_tree_config.labels.loading,
				loadError: F4_tree_config.labels.error
			},

			// Load tree
			source: {
				url: ajaxurl + '?action=f4_tree_load_tree&post_type=' + F4_menutree.currentPostType + '&post_id=' + F4_menutree.currentPostId + '&lang=' + F4_menutree.currentLanguage,
				cache: false,
				async: true
			},

			// Events
			beforeActivate: F4_menutree.onBeforeActivate,
			renderTitle: F4_menutree.onRenderTitle,
			expand: F4_menutree.onExpand,
			collapse: F4_menutree.onCollapse,
			createNode: F4_menutree.onCreateNode,

			// Extensions
			extensions: ['dnd'],
			dnd: {
				autoExpandMS: F4_menutree.dndAutoExpandDelay,
				focusOnClick: false,
				preventVoidMoves: true,
				preventRecursiveMoves: true,
				smartRevent: false,
				draggable: {
					start: F4_menutree.onDraggableStart
				},
				dragStart: F4_menutree.onDragStart,
				dragEnter: F4_menutree.onDragEnter,
				dragDrop: F4_menutree.onDragDrop
			}
		};

		F4_menutree.$container.fancytree(fancytreeConfig);
	};

	window.onbeforeunload = function(e) {
		F4_menutree.stopPoll();
	};

	window.onunload = function() {
		F4_menutree.stopPoll();
	};

	F4_menutree.startPoll = function() {
		F4_menutree.stopPoll();

		F4_menutree.pollXHR = jQuery.ajax({
			url: ajaxurl,
			type: 'POST',
			timeout: 300000, // 5 min
			async: true,
			data: {
				action: 'f4_tree_refresh',
				timestamp: F4_menutree.pollLastChange,
				post_id: F4_menutree.currentPostId,
				post_type: F4_menutree.currentPostType,
				lang: F4_menutree.currentLanguage
			},
			success: function(dataJSON) {
				try {
					var data = JSON.parse(jQuery.trim(dataJSON));

					F4_menutree.pollLastChange = data.timestamp;

					F4_menutree.reload(data.data);
					F4_menutree.startPoll();

					// Get sorted posts
					var posts_sorted = F4_menutree.getSortedPageNodes();

					F4_menutree.setMenuOrder(posts_sorted);
					F4_menutree.setSamplePermalink(data['sample-permalink']);
				} catch(err) {
					if(F4_menutree.pollErrorCount < 3) {
						F4_menutree.startPoll();
						F4_menutree.pollErrorCount++;
					}
				}
			},
			error: function(jqXHR, textStatus, errorThrown) {
				if(textStatus == 'error' && F4_menutree.pollErrorCount < 3) {
					F4_menutree.startPoll();
					F4_menutree.pollErrorCount++;
				} else if(textStatus == 'timeout') {
					F4_menutree.startPoll();
				}
			}
		});

		return F4_menutree;
	};

	F4_menutree.stopPoll = function() {
		try {
			if(F4_menutree.pollXHR != null) {
				F4_menutree.pollXHR.onreadystatechange = null;
				F4_menutree.pollXHR.abort();
				F4_menutree.pollXHR = null;
			}
		} catch(err) {
		}

		return F4_menutree;
	};

	F4_menutree.setMenuOrder = function(posts_sorted) {
		// Change menu_order for active post
		if(F4_menutree.currentPostId != -1) {
			for(var i in posts_sorted) {
				if(posts_sorted[i].type == 'post' && F4_menutree.currentPostId == posts_sorted[i].post_id) {
					jQuery('#menu_order').val(i);
					break;
				}
			}
		}
	}

	F4_menutree.setSamplePermalink = function(samplePermalink) {
		if(jQuery.trim(samplePermalink) !== '') {
			jQuery('#edit-slug-box').html(samplePermalink);
		}
	}

	F4_menutree.reload = function(data) {
		F4_menutree.tree.reload(data);

		return F4_menutree;
	}

	F4_menutree.setPersistNodeStatus = function(key, value) {
		var nodeStatusArray = {};

		if(typeof(Storage) !== 'undefined') {
			nodeStatusArray = F4_menutree.getPersistNodeStatus();

			nodeStatusArray[key] = value;

			localStorage.setItem('f4-menutree-nodes-status', JSON.stringify(nodeStatusArray))
		}

		return nodeStatusArray;
	};

	F4_menutree.getPersistNodeStatus = function(key) {
		var nodeStatusArray = {};

		if(typeof(Storage) !== 'undefined') {
			nodeStatusArray = localStorage.getItem('f4-menutree-nodes-status');

			if(nodeStatusArray == null) {
				nodeStatusArray = {};
			} else {
				nodeStatusArray = JSON.parse(nodeStatusArray);
			}
		}

		return nodeStatusArray;
	};

	F4_menutree.getSortedMenuNodes = function() {
		var menu_item_nodes = F4_menutree.tree.rootNode.findAll(function(filter_node) {
			return filter_node.parent.data.type == 'menu';
		});

		var menus_sorted = {};

		for(var menu_item_node_index in menu_item_nodes) {
			var menu_item_node = menu_item_nodes[menu_item_node_index];
			var menu_id = menu_item_node.parent.data.menu_id;

			if(typeof menus_sorted[menu_id] == 'undefined') {
				menus_sorted[menu_id] = [];
			}

			menus_sorted[menu_id].push({
				title: menu_item_node.title,
				post_id: ( typeof menu_item_node.data.post_id != 'undefined' ? menu_item_node.data.post_id : 0 ),
				menu_item_id: ( typeof menu_item_node.data.menu_item_id != 'undefined' ? menu_item_node.data.menu_item_id : 0 )
			});
		};

		return menus_sorted;
	};

	F4_menutree.getSortedPageNodes = function() {
		var post_nodes = F4_menutree.tree.rootNode.findAll(function(filter_node) {
			return filter_node.data.type == 'post';
		});

		var posts_sorted = [];

		for(var post_node_index in post_nodes) {
			var post_node_item = post_nodes[post_node_index];
			var post_node_parent = post_node_item.parent;

			posts_sorted.push({
				type: ( typeof post_node_item.data.type != 'undefined' ? post_node_item.data.type : 'custom' ),
				post_id: ( typeof post_node_item.data.post_id != 'undefined' ? post_node_item.data.post_id : 0 ),
				parent_post_id: ( typeof post_node_parent.data.post_id != 'undefined' ? post_node_parent.data.post_id : 0 )
			});
		};

		return posts_sorted;
	};

	F4_menutree.refreshSamplePermalink = function() {
		jQuery.post(ajaxurl, {
				action: 'sample-permalink',
				post_id: jQuery('#post_ID').val(),
				new_slug: jQuery('#new-post-slug').length ? jQuery('#new-post-slug').val() : jQuery('#editable-post-name').text(),
				new_title: jQuery('#title').val(),
				samplepermalinknonce: jQuery('#samplepermalinknonce').val()
			},
			function(data) {
				if(data != '-1') {
					jQuery('#edit-slug-box').html(data);
				}
			}
		);
	};

	// Events
	F4_menutree.onBeforeActivate = function(event, data) {
		return !data.node.unselectable;
	};

	F4_menutree.onRenderTitle = function(event, tree) {
		if(typeof tree.node.data.url != 'undefined') {
			return '<a class="fancytree-title" href="' + tree.node.data.url + '" title="' + tree.node.title + '">' + tree.node.title + '</a>';
		}
	};

	F4_menutree.onExpand = function(event, data) {
		F4_menutree.setPersistNodeStatus(data.node.key, 1);
	};

	F4_menutree.onCollapse = function(event, data) {
		F4_menutree.setPersistNodeStatus(data.node.key, 0);
	};

	F4_menutree.onCreateNode = function(event, data) {
		var nodeStatusArray = F4_menutree.getPersistNodeStatus(data.node.key);

		if(typeof nodeStatusArray[data.node.key] != 'undefined') {
			if(data.node.isExpanded() != nodeStatusArray[data.node.key] == 1) {
				data.node.setExpanded(nodeStatusArray[data.node.key] == 1, {noAnimation: true, noEvents: false});
			}
		}

		var tooltipText = (data.node.tooltip) ? data.node.tooltip : data.node.title;

		jQuery(data.node.li).children('.fancytree-node').children('.fancytree-title').attr('title', tooltipText);
	};

	F4_menutree.onDragStart = function(node, data) {
		F4_menutree.stopPoll();

		return true;
	};

	F4_menutree.onDraggableStart = function(event, ui) {
		var $node = jQuery(event.srcElement).parent().andSelf().filter('.node-disable-dnd');

		return $node.length == 0;
	};

	F4_menutree.onDragEnter = function(targetNode, data) {
		var sourceNode = data.otherNode;

		// Disable failed source or target nodes
		if(typeof sourceNode.data == 'undefined' || typeof targetNode.data == 'undefined') {
			return false;
		}

		// Disable dnd into level 1
		if(targetNode.getLevel() == 1 && (sourceNode.data.type == 'post' || targetNode.data.type != 'pool')) {
			return ['over'];
		}

		// Disable dns for non-post-nodes into pool
		if(sourceNode.data.type != 'post' && targetNode.parent.data.type == 'pool') {
			return false;
		}

		// Disable dnd for non-post-nodes for all levels except 2
		if(sourceNode.data.type != 'post' && targetNode.getLevel() != 2) {
			return false;
		}

		// Disable nesting for non-post-nodes
		if(sourceNode.data.type != 'post' || targetNode.data.type != 'post') {
			return ['before', 'after'];
		}

		// Disable dnd into non-hierarchival nodes
		if(!targetNode.data.allow_children || !sourceNode.data.allow_children) {
			return ['before', 'after'];
		}

		// Allow all other dnd's
		return true;
	};

	F4_menutree.onDragDrop = function(node, data) {
		var nodeParent = data.node.parent;

		data.otherNode.moveTo(node, data.hitMode);

		if(data.hitMode == 'over') {
			nodeParent = data.node;
		}

		// Expand parent post
		nodeParent.setExpanded(true);

		// Get sorted posts
		var posts_sorted = F4_menutree.getSortedPageNodes();

		// Get sorted posts
		var menus_sorted = F4_menutree.getSortedMenuNodes();

		F4_menutree.setMenuOrder(posts_sorted);

		// Move post
		jQuery.ajax({
			url: ajaxurl,
			type: 'POST',
			async: true,
			data: {
				action: 'f4_tree_move_post',
				posts_sorted: posts_sorted,
				menus_sorted: menus_sorted,
				lang: F4_menutree.currentLanguage
			},
			success: function(success) {
				F4_menutree.pollLastChange = parseInt(success);

				if(F4_menutree.currentPostId != -1) {
					F4_menutree.refreshSamplePermalink();
					F4_menutree.startPoll();
				}
			}
		});

		if(F4_menutree.currentPostId == -1) {
			location.reload();
		}
	};

})(jQuery);

jQuery(function($) {
	F4_menutree.init();

	// setTimeout(function() {
	// 	F4_menutree.startPoll(null);
	// }, 5000)
});
