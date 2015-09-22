<?php

class Pushposts
{
    public $type;
    public $posts = [];

    public function __construct($configItem)
    {
        global $bootstrapSettings;
        $this->type = $configItem;

        foreach ($bootstrapSettings->$configItem as $slug) {
            $newPost = new \stdClass();
            $newPost->done = false;
            $newPost->id = 0;
            $newPost->parentId = 0;
            $newPost->slug = $slug;
            $dir = __DIR__.'/../pages/'.$slug;
            $newPost->meta = unserialize(file_get_contents($dir.'/meta'));
            $newPost->content = file_get_contents($dir.'/content');

            $this->posts[] = $newPost;
        }

        $baseUrl = get_option('siteurl');
        $neutralUrl = 'NEUTRALURL';
        Resolver::field_search_replace($this->posts, $neutralUrl, $baseUrl);
        $this->process();
    }

    private function process()
    {
        $done = false;
        while (!$done) {
            $deferred = 0;
            foreach ($this->posts as &$post) {
                if (!$post->done) {
                    $parentId = $this->parentId($post->meta->post_parent, $this->posts);
                    if ($parentId || $post->meta->post_parent == 0) {
                        $this->updatePost($post, $parentId);
                        $post->done = true;
                    } else {
                        $deferred++;
                    }
                }
            }
            if ($deferred == 0) {
                $done = true;
            }
        }

        // check all the media.
        $uploadDir = wp_upload_dir();
        foreach (glob(dirname(__DIR__).'/media/*') as $dir) {
            $item = unserialize(file_get_contents("$dir/meta"));
            $parentId = $this->parentId($item->post_parent, $this->posts);
            if ($parentId == 0) {
                echo $parentId."\n";
                continue;
            }

            $args = [
                'name' => $item->post_name,
                'post_type' => $item->post_type,
            ];
            $id = 0;
            $existing = new WP_Query($args);
            if (!$existing->have_posts()) {
                $args = [
                    'post_title'     => $item->post_title,
                    'post_name'      => $item->post_name,
                    'post_type'      => $item->post_type,
                    'post_parent'    => $parentId,
                    'post_status'    => $item->post_status,
                    'post_mime_type' => $item->post_mime_type,
                ];
                $id = wp_insert_post($args);
            } else {
                $id = $existing->post->ID;
            }
            update_post_meta($id, '_wp_attached_file', $item->meta['file']);

            // move the file
            $src = $dir.'/'.basename($item->meta['file']);
            $trg = $uploadDir['basedir'].'/'.$item->meta['file'];
            @mkdir($uploadDir['basedir'].'/'.dirname($item->meta['file']), 0777, true);
            copy($src, $trg);
            // create metadata and other sizes
            $attachData = wp_generate_attachment_metadata($id, $trg);
            wp_update_attachment_metadata($id, $attachData);
        }
    }

    private function updatePost(&$post, $parentId)
    {
        global $wpdb;

        $pageId = $wpdb->get_var($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_name = %s", $post->slug));
        $args = array(
          'post_type'      => $post->meta->post_type,
          'post_parent'      => $parentId,
          'post_title'     => $post->meta->post_title,
          'post_content'   => $post->content,
          'post_status'    => $post->meta->post_status,
          'post_name'      => $post->meta->post_name,
          'post_exerp'     => $post->meta->post_exerp,
          'ping_status'    => $post->meta->ping_status,
          'comment_status' => $post->meta->comment_status,
          'page_template'  => $post->meta->page_template_slug,
        );

        if (!$pageId) {
            $pageId = wp_insert_post($args);
        } else {
            $args['ID'] = $pageId;
            wp_update_post($args);
        }
        $post->id = $pageId;
    }

    private function parentId($foreignParentId, $objects)
    {
        foreach ($objects as $object) {
            if ($object->meta->ID == $foreignParentId) {
                return $object->id;
            }
        }

        return 0;
    }
}
