<div class="wrap migrate-wrap">
	<form action="<?php echo esc_url( $url ) ?>" method="post">
		<p><?php _e( 'Version two of Ultimate Post Types stores its fields in a different way, which requires you to migrate your post types and taxonomies from version one, in order to use them.', 'ultimate-post-types' ) ?></p>

		<p><?php _e( 'The process will not delete your old post types and taxonomies, so you can go back to the previous version if you experience compatibility issues.', 'ultimate-post-types' ) ?></p>

		<p><strong><?php _e( 'The following post types and taxonomies will be migrated:', 'ultimate-post-types' ) ?></strong></p>

		<ul class="containers-list">
			<?php foreach ( $items as $item ): ?>
			<li>
				<em><?php echo esc_html( $item->post_title ) ?></em>
			</li>
			<?php endforeach ?>
		</ul>

		<button type="submit" class="button-primary uf-button">
			<span class="dashicons dashicons-slides uf-button-icon"></span>
			<span class="uf-button-text"><?php _e( 'Migrate', 'ultimate-post-types' ) ?></span>
		</button>

		<?php wp_nonce_field( 'uf-migrate-pts' ) ?>
		<input type="hidden" name="uf-ui-migrate" value="1" />
	</form>
</div>
