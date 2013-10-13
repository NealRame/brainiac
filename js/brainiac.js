(function ($, globals) {

	globals.brainiac = globals.brainiac || {};

	brainiac.Task = Backbone.Model.extend({
		defaults: {
			title: "Task Title",
			text: "Blah Blah Blah",
			keywords: "word1, word2, word3, ..."
		},
		initialize: function Task() {
			this.on("error", function (model, xhr, options) {
				console.log("Failed to save model:", model);
			});
		},
		validate: function (attributes, options) {
			if (attributes.title === '') {
				return "Document title cannot be empty !";
			}
		},
		getId: function () {
			return this.get('id');
		},
		setId: function (value) {
			this.set({id: value});
		},
		getTitle: function () {
			return this.get('title');
		},
		setTitle: function (value) {
			this.set({title: value}, {validate: true});
		},
		getText: function () {
			return this.get('text');
		},
		setText: function (value) {
			this.set({text: value});
		},
		getKeywords: function () {
			return this.get('keywords');
		},
		setKeywords: function (value) {
			this.set({keywords: value});
		}
	});

	brainiac.TaskList = Backbone.Collection.extend({
		model: brainiac.Task,
		initialize: function () {
		},
		url: function () {
			return "collection.php/Tasks";
		}
	});

	brainiac.TaskView = Backbone.View.extend({
		el: '#app-container',
		initialize: function () {
			var enabled = false;
			this.isEnabled = function () {
				return enabled;
			};
			this.setEnable = function (flag) {
				enabled = flag;
				return this;
			};
			this.toggle = function () {
				enabled = ! enabled;
				return this;
			};
			this.template = _.template($('#task-template').html());
		},
		setModel: function (model) {
			// deconnect this view from previous model events
			if (this.model) {
				this.model.off(null, null, this);
			}
			this.model = model;
			// connect this view to the new model if it is not null
			if (this.model) {
				this.model.bind('change', this.render, this);
			}
			this.render();
			return this;
		},
		render: function () {
			if (this.isEnabled()) {
				if (this.model) {
					this.$el.html(this.template(this.model.toJSON()));
				}
			}
			return this;
		}
	});

	brainiac.TaskEditView = Backbone.View.extend({
		el: '#app-container',
		initialize: function () {
			var enabled = false;
			this.isEnabled = function () {
				return enabled;
			};
			this.setEnable = function (flag) {
				if (flag) {
					this.delegateEvents({
						'click input.abort': 'abort',
						'click input.save': 'save',
					});
					enabled = true;
				} else {
					this.undelegateEvents();
					enabled = false;
				}
				return this;
			};
			this.toggle = function () {
				return setEnable(! enabled);
			};
			this.template = _.template($('#task-edit-template').html());
		},
		render: function () {
			if (this.isEnabled()) {
				if (this.model) {
					this.$el.html(this.template());
					this.$('.title').val(this.model.getTitle());
					this.$('.text').val(this.model.getText());
					this.$('.keywords').val(this.model.getKeywords());
				}
			}
		},
		setCollection: function (collection) {
			// deconnect this view from previous collection events
			if (this.collection) {
				this.collection.off(null, null, this);
			}
			this.collection = collection;
		},
		edit: function (id) {
			this.model = (this.collection || brainiac.collection).get(id);
			this.setEnable(true);
			this.render();
		},
		create: function () {
			this.model = new brainiac.Task();
			this.setEnable(true);
			this.render();
		},
		abort: function (ev) {
			ev.preventDefault();
			if (this.model.isNew()) {
				brainiac.router.navigate("list", {trigger: true, replace: true});
			} else {
				brainiac.router.navigate("/task/" + this.model.getId(), {trigger: true, replace: true});
			}
		},
		save: function (ev) {
			ev.preventDefault();
			this.model.setTitle($('.title').val());
			this.model.setText($('.text').val());
			this.model.setKeywords($('.keywords').val());
			if (this.model.isNew()) {
				(this.collection || brainiac.collection).add(this.model);
			}
			this.model.save({}, {
				success: function (model, response, options) {
//#REMOVE
					console.log("Youhou!");
					console.log(response);
					console.log(options);
//#END
					if (brainiac.router) {
						brainiac.router.navigate("/task/" + model.getId(), {trigger: true, replace: true});
					}
				},
				error: function () {
//#REMOVE
					console.log("Doh!");
					console.log(response);
					console.log(options);
//#END
				}
			});
		},
		error: function (model, error) {
			console.log(model, error);
			return this;
		},
		success: function (model) {
			console.log(model);
			model.save();
		}
	});
	
	brainiac.TaskListView = Backbone.View.extend({
		el: '#app-container',
		initialize : function () {
			var enabled = false;
			this.isEnabled = function () {
				return enabled;
			};
			this.setEnable = function (flag) {
				enabled = flag;
				return this;
			};
			this.toggle = function () {
				enabled = ! enabled;
				return this;
			};
			this.template = _.template($('#task-list-template').html());
		},
		setCollection: function (collection) {
			// deconnect this view from previous collection events
			if (this.collection) {
				this.collection.off(null, null, this);
			}
			this.collection = collection;
		},
		render: function () {
			if (this.isEnabled()) {
				var collection = this.collection || brainiac.collection;
				this.$el.html(this.template({tasks: collection.toJSON()}));
			}
			return this;
		}
	});

	brainiac.Router = Backbone.Router.extend({
		initialize: function () {
			var views = {
				edit: null,
				list: null,
				read: null
			};

			this.readView = function () {
				if (! views.read) {
					views.read = new brainiac.TaskView();
				}
				return views.read;
			};

			this.editView = function () {
				if (! views.edit) {
					views.edit = new brainiac.TaskEditView();	
				}
				return views.edit;
			};

			this.listView = function () {
				if (! views.list) {
					views.list = new brainiac.TaskListView();
				}
				return views.list;
			};

			var reset = function () {
				for (view in this.views) {
					this.views[view].setEnable(false);
				}				
			};

			this.route("task/:id(/:action)", "task", function (id, action) {
				reset();
				var router = this;
				var task = brainiac.collection.get(id);
				if (task) {
					switch (action) {
					case "edit":
						this.editView().edit(id);
						break;

					case "delete":
						task.destroy({
							success: function (model, response) {
								router.navigate('/list', { trigger: true, replace: true });
							}
						});
						break;

					default:
						this.readView().setModel(task).setEnable(true).render();
						break;
					}
				} else {
					this.navigate('/list', { trigger: true, replace: true});
				}
			});

			this.route("create", "create", function () {
				reset();
				this.editView().create();
			});

			this.route("list", "list", function () {
				reset();
				this.listView().setEnable(true).render();
			});
		},
		start: function () {
			var hash = window.location.hash ? window.location.hash.substring(1) : "list";
			Backbone.history.start();
			this.navigate(hash, { trigger: true, replace: true});
		}
	});

	brainiac.collection = new brainiac.TaskList();
	brainiac.router = new brainiac.Router();

})(Zepto, window);