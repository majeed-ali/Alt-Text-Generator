<?php
/**
 * Plugin Name: Auto Alt Text from Title or Description
 * Description: Lists all images with checkboxes and allows generating ALT text from the image title or description.
 * Version: 1.0
 * Author: Abdul Majeed Ali
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'admin_menu', 'aaf_add_admin_page' );
function aaf_add_admin_page() {
    add_media_page(
        'Auto Alt Text Generator',
        'Alt Text Generator',
        'manage_options',
        'aaf-alt-generator',
        'aaf_render_admin_page'
    );
}

function aaf_render_admin_page() {
    // Handle form submission
    if ( isset( $_POST['aaf_generate_alt'] ) && check_admin_referer( 'aaf_alt_nonce', 'aaf_alt_nonce_field' ) ) {
        $selected = isset( $_POST['attachments'] ) ? array_map( 'intval', $_POST['attachments'] ) : array();
        $count = 0;
        foreach ( $selected as $attach_id ) {
            $post = get_post( $attach_id );
            if ( ! $post || $post->post_type !== 'attachment' ) {
                continue;
            }
            $title = trim( $post->post_title );
            $desc  = trim( $post->post_excerpt );
            $alt    = '';
            if ( ! empty( $title ) ) {
                $alt = $title;
            } elseif ( ! empty( $desc ) ) {
                $alt = $desc;
            }
            if ( $alt !== '' ) {
                update_post_meta( $attach_id, '_wp_attachment_image_alt', sanitize_text_field( $alt ) );
                $count++;
            }
        }
        echo '<div class="updated notice"><p>' . esc_html( sprintf( '%d image(s) updated with ALT text.', $count ) ) . '</p></div>';
    }

    // Fetch attachments
    $paged = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
    $args = array(
        'post_type'      => 'attachment',
        'post_mime_type' => 'image',
        'post_status'    => 'inherit',
        'posts_per_page' => 20,
        'paged'          => $paged,
        'orderby'        => 'date',
        'order'          => 'DESC',
    );
    $query = new WP_Query( $args );
    ?>
    <div class="wrap">
        <h1>Auto Alt Text Generator</h1>
        <form method="post">
            <?php wp_nonce_field( 'aaf_alt_nonce', 'aaf_alt_nonce_field' ); ?>
            <table class="wp-list-table widefat fixed striped media">
                <thead>
                    <tr>
                        <td id="cb" class="manage-column column-cb check-column">
                            <label class="screen-reader-text" for="cb-select-all-1">Select All</label>
                            <input id="cb-select-all-1" type="checkbox" />
                        </td>
                        <th scope="col" class="manage-column">Thumbnail</th>
                        <th scope="col" class="manage-column">Filename</th>
                        <th scope="col" class="manage-column">Title</th>
                        <th scope="col" class="manage-column">Description</th>
                        <th scope="col" class="manage-column">Current ALT</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ( $query->posts as $post ) :
                    $alt = get_post_meta( $post->ID, '_wp_attachment_image_alt', true ); ?>
                    <tr>
                        <th scope="row" class="check-column">
                            <input type="checkbox" name="attachments[]" value="<?php echo esc_attr( $post->ID ); ?>" />
                        </th>
                        <td><?php echo wp_get_attachment_image( $post->ID, array( 80, 80 ) ); ?></td>
                        <td><?php echo esc_html( basename( get_attached_file( $post->ID ) ) ); ?></td>
                        <td><?php echo esc_html( $post->post_title ); ?></td>
                        <td><?php echo esc_html( $post->post_excerpt ); ?></td>
                        <td><?php echo esc_html( $alt ); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <p>
                <button type="submit" name="aaf_generate_alt" class="button button-primary">Create ALT Text</button>
            </p>
        </form>
        <?php
        // Pagination
        $big = 999999999;
        echo paginate_links( array(
            'base'      => add_query_arg( 'paged', '%#%' ),
            'format'    => '?paged=%#%',
            'current'   => $paged,
            'total'     => $query->max_num_pages,
        ) );
        ?>
    </div>
    <script>
        // Select All checkbox
        document.addEventListener('DOMContentLoaded', function() {
            const cbAll = document.getElementById('cb-select-all-1');
            cbAll.addEventListener('change', function() {
                const checkboxes = document.querySelectorAll('input[name="attachments[]"]');
                checkboxes.forEach(cb => cb.checked = cbAll.checked);
            });
        });
    </script>
    <?php
}
