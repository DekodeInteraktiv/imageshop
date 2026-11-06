/**
 * Implement filters to the media library modal.
 *
 * Courtesy of Daniel Bachhuber (danielbachhuber.com)
 */

(function(){
	let ImageshopOriginFilters = wp.media.view.AttachmentFilters.extend({
		id: 'imageshop-media-library-origin',

		createFilters: function() {
			var filters = {};

			filters.all = {
				// Change this: use whatever default label you'd like
				text: ( ImageshopMediaLibrary.labels ? ImageshopMediaLibrary.labels.origins.imageshop : 'Search Imageshop' ),
				props: {
					// Change this: key needs to be the WP_Query var for the taxonomy
					imageshop_origin: 'imageshop'
				},
				priority: 10
			}

			filters.wp = {
				// Change this: use whatever default label you'd like
				text: ( ImageshopMediaLibrary.labels ? ImageshopMediaLibrary.labels.origins.wordpress : 'Search WordPress library' ),
				props: {
					// Change this: key needs to be the WP_Query var for the taxonomy
					imageshop_origin: 'wordpress'
				},
				priority: 10
			}

			this.filters = filters;
		}
	})

	/**
	 * Create a new MediaLibraryTaxonomyFilter we later will instantiate
	 */
	let ImageshopInterfaceFilters = wp.media.view.AttachmentFilters.extend({
		id: 'imageshop-media-library-interface',

		select: function() {
			var current = this.model.get( 'imageshop_interface' );
			var value = 'all';

			_.find( this.filters, function( filter, id ) {
				// eslint-disable-next-line eqeqeq
				if ( filter.props && filter.props.imageshop_interface != null && current == filter.props.imageshop_interface ) {
					value = id;
					return true;
				}
			} );

			this.$el.val( value );
		},

		createFilters: function() {
			var filters = {};
			// Formats the 'terms' we've included via wp_localize_script()
			_.each( ImageshopMediaLibrary.interfaces || [], function( value, index ) {
				if ( value.Id && value.Name ) {
					filters[ value.Id ] = {
						text: value.Name,
						props: {
							// Change this: key needs to be the WP_Query var for the taxonomy
							imageshop_interface: value.Id,
							dataTest: 'nope',
						},
						selected: ( ImageshopMediaLibrary.default_interface.toString() === value.Id.toString() ),
					};
				}
			});
			filters.all = {
				// Change this: use whatever default label you'd like
				text:  ( ImageshopMediaLibrary.labels ? ImageshopMediaLibrary.labels.interfaces.all : 'All interfaces' ),
				props: {
					// Change this: key needs to be the WP_Query var for the taxonomy
					imageshop_interface: '0'
				},
				priority: 10
			};
			this.filters = filters;
		}
	});

	let ImageshopPostsPerPageFilter = wp.media.view.AttachmentFilters.extend({
		id: 'imageshop-posts-per-page',

		createFilters: function() {
			var filters = {};

			filters.all = {
				// Change this: use whatever default label you'd like
				text: ( ImageshopMediaLibrary.labels ? ImageshopMediaLibrary.labels.pagination.all : '25 results per page' ),
				props: {
					// Change this: key needs to be the WP_Query var for the taxonomy
					posts_per_page: 25
				},
				priority: 10
			};
			this.filters = filters;
		}
	});

	let ImageshopLanguageFilter = wp.media.view.AttachmentFilters.extend({
		id: 'imageshop-media-library-language',

		createFilters: function() {
			var filters = {};
			var defaultFilter = {
				slug: '',
				language: {
					label: ''
				}
			};

			if ( ImageshopMediaLibrary.languages ) {
				for ( const[ slug, language ] of Object.entries( ImageshopMediaLibrary.languages ) ) {
					if ( language.default ) {
						defaultFilter = {
							slug,
							language,
						};
					}

					filters[ slug ] = {
						text: language.label,
						props: {
							imageshop_language: slug,
						}
					};
				}
			}
			filters.all = {
				// Change this: use whatever default label you'd like
				text: ( ImageshopMediaLibrary.labels ? ImageshopMediaLibrary.labels.language.all : 'Language' ),
				props: {
					// Change this: key needs to be the WP_Query var for the taxonomy
					imageshop_language: ''
				},
				priority: 10
			};
			this.filters = filters;
		}
	})

	let ImageshopCategoryFilters = wp.media.view.AttachmentFilters.extend({
		id: 'imageshop-media-library-category',

		createFilters: function() {
			var filters = {};
			var incremental = 0;

			if ( ImageshopMediaLibrary.categories ) {
				ImageshopMediaLibrary.categories.map( ( category ) => {
					incremental++;
					filters[ incremental ] = {
						text: category.name,
						props: {
							// Change this: key needs to be the WP_Query var for the taxonomy
							imageshop_category: category.id,
						}
					};

					if ( category.categoryroots ) {
						category.categoryroots.forEach( ( child ) => {
							incremental++;
							filters[ incremental ] = {
								text: ' - ' + child.Root.CategoryName,
								props: {
									// Change this: key needs to be the WP_Query var for the taxonomy
									imageshop_category: child.Root.CategoryID,
								}
							};
						} );
					}
				} );
			}
			filters.all = {
				// Change this: use whatever default label you'd like
				text: ( ImageshopMediaLibrary.labels ? ImageshopMediaLibrary.labels.categories.all : 'All categories' ),
				props: {
					// Change this: key needs to be the WP_Query var for the taxonomy
					imageshop_category: ''
				},
				priority: 10
			};
			this.filters = filters;
		}
	});

	/**
	 * Extend and override wp.media.view.AttachmentsBrowser to include our new filter
	 */
	let AttachmentsBrowser = wp.media.view.AttachmentsBrowser;
	wp.media.view.AttachmentsBrowser = wp.media.view.AttachmentsBrowser.extend({
		initialize: function() {
			this.collection.props.set( {
				posts_per_page: 25,
				imageshop_interface: ImageshopMediaLibrary.default_interface,
			}, { silent: true } );
			AttachmentsBrowser.prototype.initialize.call( this );
		},
		createToolbar: function() {
			// Make sure to load the original toolbar
			AttachmentsBrowser.prototype.createToolbar.call( this );

			this.toolbar.set( 'ImageshopOriginFiltersLabel', new wp.media.view.Label({
				value: ( ImageshopMediaLibrary.labels ? ImageshopMediaLibrary.labels.origins.label : 'Media library source origin' ),
				attributes: {
					'for': 'imageshop-media-library-origin'
				},
				priority: -75
			}).render() );
			this.toolbar.set( 'ImageshopOriginFilters', new ImageshopOriginFilters({
				controller: this.controller,
				model:      this.collection.props,
				priority: -75
			}).render() );

			this.toolbar.set( 'ImageshopInterfaceFiltersLabel', new wp.media.view.Label({
				value: ( ImageshopMediaLibrary.labels ? ImageshopMediaLibrary.labels.interfaces.label : 'Imageshop Interface' ),
				attributes: {
					'for': 'imageshop-media-library-interface'
				},
				priority: -75
			}).render() );
			this.toolbar.set( 'ImageshopInterfaceFilters', new ImageshopInterfaceFilters({
				controller: this.controller,
				model:      this.collection.props,
				priority: -75
			}).render() );

			this.toolbar.set( 'ImageshopLanguageFilterLabel', new wp.media.view.Label({
				value: ( ImageshopMediaLibrary.labels ? ImageshopMediaLibrary.labels.language.label : 'Imageshop Language' ),
				attributes: {
					'for': 'imageshop-media-library-language'
				},
				priority: -75
			}).render() );
			this.toolbar.set( 'ImageshopLanguageFilter', new ImageshopLanguageFilter({
				controller: this.controller,
				model: this.collection.props,
				priority: -75
			}).render() );

			this.toolbar.set( 'ImageshopCategoryFiltersLabel', new wp.media.view.Label({
				value: ( ImageshopMediaLibrary.labels ? ImageshopMediaLibrary.labels.categories.label : 'Imageshop Category' ),
				attributes: {
					'for': 'imageshop-media-library-category'
				},
				priority: -75
			}).render() );
			this.toolbar.set( 'ImageshopCategoryFilters', new ImageshopCategoryFilters({
				controller: this.controller,
				model:      this.collection.props,
				priority: -75
			}).render() );

			this.toolbar.set( 'ImageshopPostsPerPageFilterLabel', new wp.media.view.Label({
				value: ( ImageshopMediaLibrary.labels ? ImageshopMediaLibrary.labels.pagination.label : 'Results per page' ),
				attributes: {
					'for': 'imageshop-posts-per-page'
				},
				priority: -75
			}).render() );
			this.toolbar.set( 'ImageshopPostsPerPageFilter', new ImageshopPostsPerPageFilter({
				controller: this.controller,
				model:      this.collection.props,
				priority: -75
			}).render() );

			let ImageshopInterfaceDOM,
				ImageshopCategoryDOM,
				ImageshopSelectorTimer;

			const setSelectors = () => {
				ImageshopInterfaceDOM = document.getElementById( 'imageshop-media-library-interface' );
				ImageshopCategoryDOM = document.getElementById( 'imageshop-media-library-category' );
			}

			setSelectors()

			const getImageshopCategories = ( interfaceId ) => {
				wp.apiFetch( {
					path: '/imageshop/v1/categories/' + interfaceId,
					method: 'GET'
				} ).then( ( response ) => {
					ImageshopMediaLibrary.categories = response;

					this.toolbar.set( 'ImageshopCategoryFilters', new ImageshopCategoryFilters({
						controller: this.controller,
						model:      this.collection.props,
						priority: -75
					}).render() );
				} );
			}

			const attachInterfaceChangeHandler = () => {
				ImageshopInterfaceDOM.addEventListener( 'change', () => {
					this.collection.props.set( { imageshop_category: '' } );
					getImageshopCategories( ImageshopInterfaceDOM.value );
				} );
			}

			if ( ImageshopInterfaceDOM && ImageshopCategoryDOM ) {
				attachInterfaceChangeHandler();
			} else {
				ImageshopSelectorTimer = setInterval( () => {
					setSelectors();

					if ( ImageshopInterfaceDOM && ImageshopCategoryDOM ) {
						clearInterval( ImageshopSelectorTimer );

						attachInterfaceChangeHandler();
					}
				}, 500 );
			}
		}
	});
})();

/* global ImageshopMediaLibrary, _ */

/**
 * Prepare Imageshop media before it is inserted.
 *
 * Imageshop URLs only work once confirmed, so unconfirmed media is registered when a
 * selection button (Select, Insert, etc.) is clicked, never while browsing.
 */
( function () {
	if (
		! window.wp ||
		! wp.media ||
		! wp.media.view ||
		! wp.apiFetch ||
		! window.ImageshopMediaLibrary
	) {
		return;
	}

	const labels = ImageshopMediaLibrary.labels.prepare;

	const getPending = function ( view ) {
		const state =
			view.controller && view.controller.state && view.controller.state();
		const selection = state && state.get( 'selection' );

		return selection && selection.models
			? selection.filter( ( model ) => model.get( 'imageshopPending' ) )
			: null;
	};

	const prepare = function ( models ) {
		models.forEach( ( model ) =>
			model.set( { imageshopPreparing: true, imageshopError: null } )
		);
		if ( wp.a11y && wp.a11y.speak ) {
			wp.a11y.speak( labels.preparing );
		}

		return Promise.all(
			_.chunk( models, 50 ).map( ( chunk ) =>
				wp
					.apiFetch( {
						path: '/imageshop/v1/attachments/prepare',
						method: 'POST',
						data: {
							ids: chunk.map( ( model ) => model.get( 'id' ) ),
						},
					} )
					.then( ( response ) => {
						const items = ( response && response.items ) || {};

						chunk.forEach( ( model ) => {
							const item = items[ model.get( 'id' ) ];

							model.set(
								item && item.success
									? Object.assign( {}, item.attachment, {
											imageshopPending: false,
											imageshopPreparing: false,
											imageshopError: null,
										} )
									: {
											imageshopPreparing: false,
											imageshopError:
												( item && item.message ) ||
												labels.failed,
										}
							);
						} );
					} )
					.catch( ( error ) =>
						chunk.forEach( ( model ) =>
							model.set( {
								imageshopPreparing: false,
								imageshopError:
									( error && error.message ) || labels.failed,
							} )
						)
					)
			)
		).then( () => {
			const prepared = models.every(
				( model ) => ! model.get( 'imageshopPending' )
			);

			if ( ! prepared ) {
				// eslint-disable-next-line no-alert
				window.alert(
					[ labels.failed ]
						.concat(
							_.uniq(
								models
									.map( ( model ) =>
										model.get( 'imageshopError' )
									)
									.filter( Boolean )
							)
						)
						.join( '\n\n' )
				);
			}

			return prepared;
		} );
	};

	const Button = wp.media.view.Button;
	const buttonClick = Button.prototype.click;

	Button.prototype.click = function ( event ) {
		const requiresSelection =
			this.options.requires && this.options.requires.selection;
		const pending = requiresSelection ? getPending( this ) : null;

		if ( ! pending || ! pending.length || this.model.get( 'disabled' ) ) {
			return buttonClick.apply( this, arguments );
		}

		event.preventDefault();

		prepare( pending ).then( ( prepared ) => {
			// The modal may have been closed while waiting.
			if ( prepared && this.$el.is( ':visible' ) ) {
				buttonClick.call( this, event );
			}
		} );
	};

	const Toolbar = wp.media.view.Toolbar;
	const toolbarInitialize = Toolbar.prototype.initialize;
	const toolbarRefresh = Toolbar.prototype.refresh;

	Toolbar.prototype.initialize = function () {
		toolbarInitialize.apply( this, arguments );

		if ( this.selection ) {
			this.selection.on(
				'change:imageshopPreparing',
				this.refresh,
				this
			);
		}
	};

	Toolbar.prototype.refresh = function () {
		toolbarRefresh.apply( this, arguments );

		const preparing = ( getPending( this ) || [] ).some( ( model ) =>
			model.get( 'imageshopPreparing' )
		);

		// Show progress in the selection buttons, and restore their text afterwards.
		_.each( this._views, ( button ) => {
			if (
				! button.model ||
				! button.options ||
				! button.options.requires ||
				! button.options.requires.selection
			) {
				return;
			}

			if ( preparing ) {
				button.imageshopText =
					button.imageshopText || button.model.get( 'text' );
				button.model.set( { disabled: true, text: labels.preparing } );
			} else if ( button.imageshopText ) {
				button.model.set( 'text', button.imageshopText );
				delete button.imageshopText;
			}
		} );
	};
} )();
jQuery( document ).ready( function( $ ) {
	$( document ).on( 'click', '.imageshop-edit-cancel', function( event ) {
		event.preventDefault();

		$( '.imageshop-edit-form' ).hide();
		$( '.imageshop-edit-form-feedback' ).hide();
		$( '.imageshop-edit-toggle-visibility' )
			.prev( '.links-separator' )
			.addBack()
			.show();
	} );
	$( document ).on( 'click', '.imageshop-edit-toggle-visibility', function( event ) {
		event.preventDefault();

		$( '.imageshop-edit-form' ).show();
		$( '.imageshop-edit-toggle-visibility' )
			.prev( '.links-separator' )
			.addBack()
			.hide();
	} );
	$( document ).on( 'click', '.imageshop_perform_edit', function( event ) {
		event.preventDefault();

		const form = $( this ).closest( 'form' )[0];
		const data = new FormData( form );
		const editorFormFeedback = $( '.imageshop-edit-form-feedback' );

		editorFormFeedback.hide();

		$.ajax( {
			type: 'POST',
			url: ImageshopMediaLibrary.rest_url.update_metadata,
			data: data,
			processData: false,
			contentType: false,
			dataType: 'json'
		} )
			.done( function ( response ) {
				$( '#attachment-details-alt-text' ).val( response.alt );
				$( '#attachment-details-two-column-alt-text' ).val( response.alt );
				$( '#attachment-details-title' ).val( response.title );
				$( '#attachment-details-two-column-title' ).val( response.title );
				$( '#attachment-details-caption' ).val ( response.caption );
				$( '#attachment-details-two-column-caption' ).val ( response.caption );
				$( '#attachment-details-description' ).val( response.description );
				$( '#attachment-details-two-column-description' ).val( response.description );
			} )
			.fail( function ( response ) {
				editorFormFeedback.show().text( response.responseJSON.message );
			} );
	} );

	$( document ).on( 'click', '.imageshop-flush-cache', function( event ) {
		event.preventDefault();

		const feedbackWrapper = $( '.imageshop-ajax-generic-feedback' );

		// Clear existing AJAX feedbacks and states.
		feedbackWrapper
			.removeClass( 'notice-error' )
			.removeClass( 'notice-success' )
			.removeClass( 'notice-warning' )
			.hide();

		$.ajax( {
			type: 'POST',
			url: ImageshopMediaLibrary.rest_url.flush_cache,
			data: {
				_wpnonce: ImageshopMediaLibrary.nonce.rest,
				edit_nonce: $( this ).data( 'flush-nonce' ),
				id: $( this ).data( 'imageshop-id' )
			},
			dataType: 'json'
		} )
			.done( function ( response ) {
				feedbackWrapper.addClass( 'notice-success' ).text( response.message ).show();
			} )
			.fail( function ( response ) {
				console.log( response );
				feedbackWrapper.addClass( 'notice-error' ).text( response.responseJSON.message ).show();
			} );
	} );

	$( document ).on( 'click', '.imageshop-check-link', function( event ) {
		event.preventDefault();

		const button = $( this );
		const feedbackWrapper = $( '.imageshop-ajax-generic-feedback' );

		feedbackWrapper
			.removeClass( 'notice-error' )
			.removeClass( 'notice-success' )
			.removeClass( 'notice-warning' )
			.hide();

		button.prop( 'disabled', true );

		$.ajax( {
			type: 'POST',
			url: ImageshopMediaLibrary.rest_url.check_link,
			data: {
				_wpnonce: ImageshopMediaLibrary.nonce.rest,
				id: button.data( 'imageshop-id' )
			},
			dataType: 'json'
		} )
			.done( function ( response ) {
				feedbackWrapper.addClass( 'notice-success' ).text( response.message ).show();
			} )
			.fail( function ( response ) {
				feedbackWrapper.addClass( 'notice-error' ).text( response.responseJSON ? response.responseJSON.message : response.statusText ).show();
			} )
			.always( function () {
				button.prop( 'disabled', false );
			} );
	} );
} );
