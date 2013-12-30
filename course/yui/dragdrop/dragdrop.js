YUI.add('moodle-course-dragdrop', function(Y) {

    var CSS = {
        ACTIONAREA: '.actions',
        ACTIVITY : 'activity',
        ACTIVITYINSTANCE : 'activityinstance',
        CONTENT : 'content',
        COURSECONTENT : 'course-content',
        EDITINGMOVE : 'editing_move',
        ICONCLASS : 'iconsmall',
        JUMPMENU : 'jumpmenu',
        LEFT : 'left',
        LIGHTBOX : 'lightbox',
        MOVEDOWN : 'movedown',
        MOVEUP : 'moveup',
        PAGECONTENT : 'page-content',
        RIGHT : 'right',
        SECTION : 'section',
        SECTIONADDMENUS : 'section_add_menus',
        SECTIONHANDLE : 'section-handle',
        SUMMARY : 'summary',
        SECTIONDRAGGABLE: 'sectiondraggable'
    };

    var DRAGSECTION = function() {
        DRAGSECTION.superclass.constructor.apply(this, arguments);
    };
    Y.extend(DRAGSECTION, M.core.dragdrop, {
        sectionlistselector : null,

        initializer : function() {
            // Set group for parent class
            this.groups = [ CSS.SECTIONDRAGGABLE ];
            this.samenodeclass = M.course.format.get_sectionwrapperclass();
            this.parentnodeclass = M.course.format.get_containerclass();

            // Check if we are in single section mode
            if (Y.Node.one('.'+CSS.JUMPMENU)) {
                return false;
            }
            // Initialise sections dragging
            this.sectionlistselector = M.course.format.get_section_wrapper(Y);
            if (this.sectionlistselector) {
                this.sectionlistselector = '.'+CSS.COURSECONTENT+' '+this.sectionlistselector;

                this.setup_for_section(this.sectionlistselector);

                // Make each li element in the lists of sections draggable
                var del = new Y.DD.Delegate({
                    container: '.'+CSS.COURSECONTENT,
                    nodes: '.' + CSS.SECTIONDRAGGABLE,
                    target: true,
                    handles: ['.'+CSS.LEFT],
                    dragConfig: {groups: this.groups}
                });
                del.dd.plug(Y.Plugin.DDProxy, {
                    // Don't move the node at the end of the drag
                    moveOnEnd: false
                });
                del.dd.plug(Y.Plugin.DDConstrained, {
                    // Keep it inside the .course-content
                    constrain: '#'+CSS.PAGECONTENT,
                    stickY: true
                });
                del.dd.plug(Y.Plugin.DDWinScroll);
            }
        },

         /**
         * Apply dragdrop features to the specified selector or node that refers to section(s)
         *
         * @param baseselector The CSS selector or node to limit scope to
         * @return void
         */
        setup_for_section : function(baseselector) {
            Y.Node.all(baseselector).each(function(sectionnode) {
                // Determine the section ID
                var sectionid = Y.Moodle.core_course.util.section.getId(sectionnode);

                // We skip the top section as it is not draggable
                if (sectionid > 0) {
                    // Remove move icons
                    var movedown = sectionnode.one('.'+CSS.RIGHT+' a.'+CSS.MOVEDOWN);
                    var moveup = sectionnode.one('.'+CSS.RIGHT+' a.'+CSS.MOVEUP);

                    // Add dragger icon
                    var title = M.util.get_string('movesection', 'moodle', sectionid);
                    var cssleft = sectionnode.one('.'+CSS.LEFT);

                    if ((movedown || moveup) && cssleft) {
                        cssleft.setStyle('cursor', 'move');
                        cssleft.appendChild(this.get_drag_handle(title, CSS.SECTIONHANDLE, 'icon', true));

                        if (moveup) {
                            moveup.remove();
                        }
                        if (movedown) {
                            movedown.remove();
                        }

                        // This section can be moved - add the class to indicate this to Y.DD.
                        sectionnode.addClass(CSS.SECTIONDRAGGABLE);
                    }
                }
            }, this);
        },

        /*
         * Drag-dropping related functions
         */
        drag_start : function(e) {
            // Get our drag object
            var drag = e.target;
            // Creat a dummy structure of the outer elemnents for clean styles application
            var containernode = Y.Node.create('<'+M.course.format.get_containernode()+'></'+M.course.format.get_containernode()+'>');
            containernode.addClass(M.course.format.get_containerclass());
            var sectionnode = Y.Node.create('<'+ M.course.format.get_sectionwrappernode()+'></'+ M.course.format.get_sectionwrappernode()+'>');
            sectionnode.addClass( M.course.format.get_sectionwrapperclass());
            sectionnode.setStyle('margin', 0);
            sectionnode.setContent(drag.get('node').get('innerHTML'));
            containernode.appendChild(sectionnode);
            drag.get('dragNode').setContent(containernode);
            drag.get('dragNode').addClass(CSS.COURSECONTENT);
        },

        drag_dropmiss : function(e) {
            // Missed the target, but we assume the user intended to drop it
            // on the last last ghost node location, e.drag and e.drop should be
            // prepared by global_drag_dropmiss parent so simulate drop_hit(e).
            this.drop_hit(e);
        },

        get_section_index: function(node) {
            var sectionlistselector = '.' + CSS.COURSECONTENT + ' ' + M.course.format.get_section_selector(Y),
                sectionList = Y.all(sectionlistselector),
                nodeIndex = sectionList.indexOf(node),
                zeroIndex = sectionList.indexOf(Y.one('#section-0'));

            return (nodeIndex - zeroIndex);
        },

        drop_hit : function(e) {
            var drag = e.drag;

            // Get references to our nodes and their IDs.
            var dragnode = drag.get('node'),
                dragnodeid = Y.Moodle.core_course.util.section.getId(dragnode),
                loopstart = dragnodeid,

                dropnodeindex = this.get_section_index(dragnode),
                loopend = dropnodeindex;

            if (dragnodeid === dropnodeindex) {
                Y.log("Skipping move - same location moving " + dragnodeid + " to " + dropnodeindex, 'debug', 'moodle-course-dragdrop');
                return;
            }

            Y.log("Moving from position " + dragnodeid + " to position " + dropnodeindex, 'debug', 'moodle-course-dragdrop');

            if (loopstart > loopend) {
                // If we're going up, we need to swap the loop order
                // because loops can't go backwards.
                loopstart = dropnodeindex;
                loopend = dragnodeid;
            }

            // Get the list of nodes.
            drag.get('dragNode').removeClass(CSS.COURSECONTENT);
            var sectionlist = Y.Node.all(this.sectionlistselector);

            // Add a lightbox if it's not there.
            var lightbox = M.util.add_lightbox(Y, dragnode);

            // Handle any variables which we must pass via AJAX.
            var params = {},
                pageparams = this.get('config').pageparams,
                varname;

            for (varname in pageparams) {
                if (!pageparams.hasOwnProperty(varname)) {
                    continue;
                }
                params[varname] = pageparams[varname];
            }

            // Prepare request parameters
            params.sesskey = M.cfg.sesskey;
            params.courseId = this.get('courseid');
            params['class'] = 'section';
            params.field = 'move';
            params.id = dragnodeid;
            params.value = dropnodeindex;

            // Perform the AJAX request.
            var uri = M.cfg.wwwroot + this.get('ajaxurl');
            Y.io(uri, {
                method: 'POST',
                data: params,
                on: {
                    start : function() {
                        lightbox.show();
                    },
                    success: function(tid, response) {
                        // Update section titles, we can't simply swap them as
                        // they might have custom title
                        try {
                            var responsetext = Y.JSON.parse(response.responseText);
                            if (responsetext.error) {
                                new M.core.ajaxException(responsetext);
                            }
                            M.course.format.process_sections(Y, sectionlist, responsetext, loopstart, loopend);
                        } catch (e) {}

                        // Update all of the section IDs - first unset them, then set them
                        // to avoid duplicates in the DOM.
                        var index;

                        // Classic bubble sort algorithm is applied to the section
                        // nodes between original drag node location and the new one.
                        var swapped = false;
                        do {
                            swapped = false;
                            for (index = loopstart; index <= loopend; index++) {
                                if (Y.Moodle.core_course.util.section.getId(sectionlist.item(index - 1)) >
                                            Y.Moodle.core_course.util.section.getId(sectionlist.item(index))) {
                                    Y.log("Swapping " + Y.Moodle.core_course.util.section.getId(sectionlist.item(index - 1)) +
                                            " with " + Y.Moodle.core_course.util.section.getId(sectionlist.item(index)));
                                    // Swap section id.
                                    var sectionid = sectionlist.item(index - 1).get('id');
                                    sectionlist.item(index - 1).set('id', sectionlist.item(index).get('id'));
                                    sectionlist.item(index).set('id', sectionid);

                                    // See what format needs to swap.
                                    M.course.format.swap_sections(Y, index - 1, index);

                                    // Update flag.
                                    swapped = true;
                                }
                            }
                            loopend = loopend - 1;
                        } while (swapped);

                        window.setTimeout(function() {
                            lightbox.hide();
                        }, 250);
                    },

                    failure: function(tid, response) {
                        this.ajax_failure(response);
                        lightbox.hide();
                    }
                },
                context:this
            });
        }

    }, {
        NAME : 'course-dragdrop-section',
        ATTRS : {
            courseid : {
                value : null
            },
            ajaxurl : {
                'value' : 0
            },
            config : {
                'value' : 0
            }
        }
    });

    var DRAGRESOURCE = function() {
        DRAGRESOURCE.superclass.constructor.apply(this, arguments);
    };
    Y.extend(DRAGRESOURCE, M.core.dragdrop, {
        initializer : function(params) {
            // Set group for parent class
            this.groups = ['resource'];
            this.samenodeclass = CSS.ACTIVITY;
            this.parentnodeclass = CSS.SECTION;
            this.resourcedraghandle = this.get_drag_handle(M.str.moodle.move, CSS.EDITINGMOVE, CSS.ICONCLASS, true);

            // Go through all sections
            var sectionlistselector = M.course.format.get_section_selector(Y);
            if (sectionlistselector) {
                sectionlistselector = '.'+CSS.COURSECONTENT+' '+sectionlistselector;
                this.setup_for_section(sectionlistselector);

                // Initialise drag & drop for all resources/activities
                var nodeselector = sectionlistselector.slice(CSS.COURSECONTENT.length+2)+' li.'+CSS.ACTIVITY;
                var del = new Y.DD.Delegate({
                    container: '.'+CSS.COURSECONTENT,
                    nodes: nodeselector,
                    target: true,
                    handles: ['.' + CSS.EDITINGMOVE],
                    dragConfig: {groups: this.groups}
                });
                del.dd.plug(Y.Plugin.DDProxy, {
                    // Don't move the node at the end of the drag
                    moveOnEnd: false,
                    cloneNode: true
                });
                del.dd.plug(Y.Plugin.DDConstrained, {
                    // Keep it inside the .course-content
                    constrain: '#'+CSS.PAGECONTENT
                });
                del.dd.plug(Y.Plugin.DDWinScroll);

                M.course.coursebase.register_module(this);
                M.course.dragres = this;
            }
        },

         /**
         * Apply dragdrop features to the specified selector or node that refers to section(s)
         *
         * @param baseselector The CSS selector or node to limit scope to
         * @return void
         */
        setup_for_section : function(baseselector) {
            Y.Node.all(baseselector).each(function(sectionnode) {
                var resources = sectionnode.one('.'+CSS.CONTENT+' ul.'+CSS.SECTION);
                // See if resources ul exists, if not create one
                if (!resources) {
                    var resources = Y.Node.create('<ul></ul>');
                    resources.addClass(CSS.SECTION);
                    sectionnode.one('.'+CSS.CONTENT+' div.'+CSS.SUMMARY).insert(resources, 'after');
                }
                resources.setAttribute('data-draggroups', this.groups.join(' '));
                // Define empty ul as droptarget, so that item could be moved to empty list
                var tar = new Y.DD.Drop({
                    node: resources,
                    groups: this.groups,
                    padding: '20 0 20 0'
                });

                // Initialise each resource/activity in this section
                this.setup_for_resource('#'+sectionnode.get('id')+' li.'+CSS.ACTIVITY);
            }, this);
        },
        /**
         * Apply dragdrop features to the specified selector or node that refers to resource(s)
         *
         * @param baseselector The CSS selector or node to limit scope to
         * @return void
         */
        setup_for_resource : function(baseselector) {
            Y.Node.all(baseselector).each(function(resourcesnode) {
                // Replace move icons
                var move = resourcesnode.one('a.'+CSS.EDITINGMOVE);
                if (move) {
                    move.replace(this.resourcedraghandle.cloneNode(true));
                }
            }, this);
        },

        drag_start : function(e) {
            // Get our drag object
            var drag = e.target;
            drag.get('dragNode').setContent(drag.get('node').get('innerHTML'));
            drag.get('dragNode').all('img.iconsmall').setStyle('vertical-align', 'baseline');
        },

        drag_dropmiss : function(e) {
            // Missed the target, but we assume the user intended to drop it
            // on the last last ghost node location, e.drag and e.drop should be
            // prepared by global_drag_dropmiss parent so simulate drop_hit(e).
            this.drop_hit(e);
        },

        drop_hit : function(e) {
            var drag = e.drag;
            // Get a reference to our drag node
            var dragnode = drag.get('node');
            var dropnode = e.drop.get('node');

            // Add spinner if it not there
            var actionarea = dragnode.one(CSS.ACTIONAREA);
            var spinner = M.util.add_spinner(Y, actionarea);

            var params = {};

            // Handle any variables which we must pass back through to
            var pageparams = this.get('config').pageparams;
            var varname;
            for (varname in pageparams) {
                params[varname] = pageparams[varname];
            }

            // Prepare request parameters
            params.sesskey = M.cfg.sesskey;
            params.courseId = this.get('courseid');
            params['class'] = 'resource';
            params.field = 'move';
            params.id = Number(Y.Moodle.core_course.util.cm.getId(dragnode));
            params.sectionId = Y.Moodle.core_course.util.section.getId(dropnode.ancestor(M.course.format.get_section_wrapper(Y), true));

            if (dragnode.next()) {
                params.beforeId = Number(Y.Moodle.core_course.util.cm.getId(dragnode.next()));
            }

            // Do AJAX request
            var uri = M.cfg.wwwroot + this.get('ajaxurl');

            Y.io(uri, {
                method: 'POST',
                data: params,
                on: {
                    start : function(tid) {
                        this.lock_drag_handle(drag, CSS.EDITINGMOVE);
                        spinner.show();
                    },
                    success: function(tid, response) {
                        var responsetext = Y.JSON.parse(response.responseText);
                        var params = {element: dragnode, visible: responsetext.visible};
                        M.course.coursebase.invoke_function('set_visibility_resource_ui', params);
                        this.unlock_drag_handle(drag, CSS.EDITINGMOVE);
                        window.setTimeout(function(e) {
                            spinner.hide();
                        }, 250);
                    },
                    failure: function(tid, response) {
                        this.ajax_failure(response);
                        this.unlock_drag_handle(drag, CSS.SECTIONHANDLE);
                        spinner.hide();
                        // TODO: revert nodes location
                    }
                },
                context:this
            });
        }
    }, {
        NAME : 'course-dragdrop-resource',
        ATTRS : {
            courseid : {
                value : null
            },
            ajaxurl : {
                'value' : 0
            },
            config : {
                'value' : 0
            }
        }
    });

    M.course = M.course || {};
    M.course.init_resource_dragdrop = function(params) {
        new DRAGRESOURCE(params);
    }
    M.course.init_section_dragdrop = function(params) {
        new DRAGSECTION(params);
    }
}, '@VERSION@', {requires:['base', 'node', 'io', 'dom', 'dd', 'dd-scroll', 'moodle-core-dragdrop', 'moodle-core-notification', 'moodle-course-coursebase', 'moodle-course-util']});
