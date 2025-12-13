/**
 * Implement filters to the media library screen.
 *
 * Courtesy of Daniel Bachhuber (danielbachhuber.com)
 */

(function(){
	let ImageshopMimeTypeFilters = wp.media.view.AttachmentFilters.extend({
		id: 'imageshop-media-library-mime-type',

		createFilters: function() {
			var filters = {};

			filters.all = {
				// Change this: use whatever default label you'd like
				text: ( ImageshopMediaLibrary.labels ? ImageshopMediaLibrary.labels.mime_type.all : 'All media types' ),
				props: {
					// Change this: key needs to be the WP_Query var for the taxonomy
					post_mime_type: 'all'
				},
				priority: 10
			}

			filters.image = {
				// Change this: use whatever default label you'd like
				text: ( ImageshopMediaLibrary.labels ? ImageshopMediaLibrary.labels.mime_type.images : 'Images' ),
				props: {
					// Change this: key needs to be the WP_Query var for the taxonomy
					post_mime_type: 'image'
				},
				priority: 10
			}
			filters.video = {
				// Change this: use whatever default label you'd like
				text: ( ImageshopMediaLibrary.labels ? ImageshopMediaLibrary.labels.mime_type.videos : 'Videos' ),
				props: {
					// Change this: key needs to be the WP_Query var for the taxonomy
					post_mime_type: 'video'
				},
				priority: 10
			}
			filters.audio = {
				// Change this: use whatever default label you'd like
				text: ( ImageshopMediaLibrary.labels ? ImageshopMediaLibrary.labels.mime_type.audio : 'Audio' ),
				props: {
					// Change this: key needs to be the WP_Query var for the taxonomy
					post_mime_type: 'audio'
				},
				priority: 10
			}
			filters.document = {
				// Change this: use whatever default label you'd like
				text: ( ImageshopMediaLibrary.labels ? ImageshopMediaLibrary.labels.mime_type.documents : 'Documents' ),
				props: {
					// Change this: key needs to be the WP_Query var for the taxonomy
					post_mime_type: 'document'
				},
				priority: 10
			}

			this.filters = filters;
		}
	})

	/**
	 * Extend and override wp.media.view.AttachmentsBrowser to include our new filter
	 */
	let AttachmentsBrowser = wp.media.view.AttachmentsBrowser;
	wp.media.view.AttachmentsBrowser = wp.media.view.AttachmentsBrowser.extend({
		createToolbar: function() {
			// Make sure to load the original toolbar
			AttachmentsBrowser.prototype.createToolbar.call( this );

			this.toolbar.set( 'ImageshopMimeTypeFiltersLabel', new wp.media.view.Label({
				value: ( ImageshopMediaLibrary.labels ? ImageshopMediaLibrary.labels.mime_type.label : 'Media library source origin' ),
				attributes: {
					'for': 'imageshop-media-library-mime-type'
				},
				priority: -75
			}).render() );
			this.toolbar.set( 'ImageshopMimeTypeFilters', new ImageshopMimeTypeFilters({
				controller: this.controller,
				model:      this.collection.props,
				priority: -75
			}).render() );
		}
	});
})();
