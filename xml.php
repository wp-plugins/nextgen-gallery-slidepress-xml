<?php
/*
Plugin Name: NextGEN Gallery Slidepress XML
Plugin URI: http://wordpress.org/extend/plugins/nextgen-gallery-slidepress-xml/
Description: Providing a slidepress compatible Media RSS feed with nextgen gallery
Version: 1.0.0
Author: Daniel Siegers
Author URI: http://www.siegers.biz
*/

/*  Copyright 2010  Daniel Siegers  (email : daniel@siegers.biz)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

if ( !defined('WP_LOAD_PATH') ) :

    /** classic root path if wp-content and plugins is below wp-config.php */
    $classic_root = dirname(dirname(dirname(dirname(__FILE__)))) . '/' ;
    
    if (file_exists( $classic_root . 'wp-load.php') )
            define( 'WP_LOAD_PATH', $classic_root);
    else
            if (file_exists( $path . 'wp-load.php') )
                    define( 'WP_LOAD_PATH', $path);
            else
                    exit("Could not find wp-load.php");

    // let's load WordPress
    require_once( WP_LOAD_PATH . 'wp-load.php');

endif;

if ($HTTP_SERVER_VARS['SCRIPT_FILENAME'] == __FILE__ && defined('NGGFOLDER')) :
    
    if (!class_exists(nggSlidepressXML)) :
        /**
        * Class to produce Media RSS nodes
        * 
        * @author 		Vincent Prat
        * @copyright 	Copyright 2008
        */
        class nggSlidepressXML {
                
                /**
                 * Function called by the wp_head action to output the RSS link for medias
                 */
                function add_sxml_alternate_link() {
                        echo "<link id='MediaRSS' rel='alternate' type='application/rss+xml' title='NextGEN Gallery RSS Feed' href='" . nggSlidepressXML::get_sxml_url() . "' />\n";		
                }
                
                /**
                 * Add the javascript required to enable PicLens/CoolIris support 
                 */
                function add_piclens_javascript() {
                        echo "\n" . '<!-- NextGeEN Gallery CoolIris/PicLens support -->';
                        echo "\n" . '<script type="text/javascript" src="http://lite.piclens.com/current/piclens_optimized.js"></script>';
                        echo "\n" . '<!-- /NextGEN Gallery CoolIris/PicLens support -->';
                }
                
                /**
                 * Get the URL of the general media RSS
                 */
                function get_sxml_url() {	
                        return NGGALLERY_URLPATH . 'xml/slidepressxml.php';
                }
                
                /**
                 * Get the URL of a gallery media RSS
                 */
                function get_gallery_sxml_url($gid, $prev_next = false) {		
                        return nggSlidepressXML::get_sxml_url() . '?' . ('gid=' . $gid . ($prev_next ? '&prev_next=true' : '') . '&mode=gallery');
                }
                
                /**
                 * Get the URL of an album media RSS
                 */
                function get_album_sxml_url($aid) {		
                        return nggSlidepressXML::get_sxml_url() . '?' . ('aid=' . $aid . '&mode=album');
                }
                
                /**
                 * Get the URL of the media RSS for last pictures
                 */
                function get_last_pictures_sxml_url($page = 0, $show = 30) {		
                        return nggSlidepressXML::get_sxml_url() . '?' . ('show=' . $show . '&page=' . $page . '&mode=last_pictures');
                }
                
                
                /**
                 * Get the XML <rss> node corresponding to a gallery
                 *
                 * @param $gallery (object) The gallery to include in RSS
                 * @param $prev_gallery (object) The previous gallery to link in RSS (null if none)
                 * @param $next_gallery (object) The next gallery to link in RSS (null if none)
                 */
                function get_gallery_sxml($gallery, $withurls = false) {
                        
                        $ngg_options = nggGallery::get_option('ngg_options');
                        //Set sort order value, if not used (upgrade issue)
                        $ngg_options['galSort'] = ($ngg_options['galSort']) ? $ngg_options['galSort'] : 'pid';
                        $ngg_options['galSortDir'] = ($ngg_options['galSortDir'] == 'DESC') ? 'DESC' : 'ASC';
                
                        $title = stripslashes(nggGallery::i18n($gallery->title));
                        $description = stripslashes(nggGallery::i18n($gallery->galdesc));
                        $link = nggSlidepressXML::get_permalink($gallery->pageid);
                        $images = nggdb::get_gallery($gallery->gid, $ngg_options['galSort'], $ngg_options['galSortDir']);
                        
                        $galleries = array($gallery);
        
                        return nggSlidepressXML::get_sxml_root_node($title, $description, $link, $galleries, $withurls);
                }
                
                /**
                 * Get the XML <rss> node corresponding to an album
                 *
                 * @param $album The album to include in RSS
                 */
                function get_album_sxml($album, $withurls = false) {
                        global $nggdb;
        
                        $title = stripslashes(nggGallery::i18n($album->name));
                        $description = '';
                        $link = nggSlidepressXML::get_permalink(0);
                        
                        $all_galleries = $nggdb->find_all_galleries();
                        $galleryIds = nggSlidepressXML::get_album_gallery_ids($album);
                        $galleries = array();
                        foreach ( $galleryIds as $id) {
                                //print_r($id);
                                $galleries[] = $all_galleries[$id];
                        }
                        
                        return $test. nggSlidepressXML::get_sxml_root_node($title, $description, $link, $galleries, $withurls);
                }
                
                function get_album_gallery_ids ($album) {
                        $galleryIds = array();
                        
                        foreach ($album->gallery_ids as $id) {
                                if ( substr($id, 0, 1) == 'a' ) {
                                        $subAlbumId = substr($id, 1);
                                        $subAlbum = nggdb::find_album($subAlbumId);
                                        $subAlbumGalleryIds = nggSlidepressXML::get_album_gallery_ids($subAlbum);
                                        foreach($subAlbumGalleryIds as $subAlbumGalleryId) {
                                                array_push($galleryIds, $subAlbumGalleryId);
                                        }
                                } else {
                                        array_push($galleryIds, $id);
                                }
                        }
                        return $galleryIds;
                }
                
                /**
                 * Get the XML <rss> node
                 */
                function get_sxml_root_node($title, $description, $link, $galleries, $withurls = false) {	
                        
                        $out = '<gallery ';
                        $out .= 'title="'. $title. '" ';
                        $out .= 'description="'. $description. '">'. "\n";
                        
                        foreach ($galleries as $gallery) {
                                $out .= nggSlidepressXML::get_gallery_sxml_node($gallery, $withurls);
                        }
                        
                        $out .= "</gallery>\n";
                        
                        return $out;
                }	
                
                /**
                 * Get the XML <title> node
                 */
                function get_gallery_sxml_node($gallery, $withurls = false, $indent = "\t") {
        
                        $ngg_options = nggGallery::get_option('ngg_options');
                        //Set sort order value, if not used (upgrade issue)
                        $ngg_options['galSort'] = ($ngg_options['galSort']) ? $ngg_options['galSort'] : 'pid';
                        $ngg_options['galSortDir'] = ($ngg_options['galSortDir'] == 'DESC') ? 'DESC' : 'ASC';
                
                        $siteurl = get_option('siteurl'). "/";
                        $title = stripslashes(nggGallery::i18n($gallery->title));
                        $description = stripslashes(nggGallery::i18n($gallery->galdesc));
                        $link = nggSlidepressXML::get_permalink($gallery->pageid);
                        $images = nggdb::get_gallery($gallery->gid, $ngg_options['galSort'], $ngg_options['galSortDir']);
                        $previewpic = nggdb::find_image($gallery->previewpic);
                        $tnpath = $previewpic->path;
                        $tn = $previewpic->filename;
        
        
                        $out = $indent;
                        $out .= '<album ';
                        $out .= 'id="'. $gallery->gid. '" ';
                        $out .= 'lgpath="'. $siteurl. $gallery->path. '/" ';
                        $out .= 'tnpath="'. $siteurl. $tnpath. '/" ';
                        $out .= 'title="'. $title. '" ';
                        $out .= 'description="'. $description. '" ';
                        $out .= 'tn="'. $siteurl. $tnpath. "/". $tn. '">'. "\n";
                        
                        foreach( $images as $image) {
                                $out .= nggSlidepressXML::get_image_sxml_node($image, $withurls);
                        }
                        
                        $out .= $indent. "</album>\n";
                        return $out;
                }	
        
                /**
                 * Get the XML <title> node
                 */
                function get_image_sxml_node($image, $withurls = false, $indent = "\t\t") {	
        
                        $title = html_entity_decode(stripslashes($image->alttext));
                        $desc = html_entity_decode(stripslashes($image->description));
        
                        return $indent.
                                '<img src="'. $image->filename . '" '.
                                'id="'. $image->pid. '" '.
                                'title="'. nggGallery::i18n($title). '" '.
                                'caption="'. nggGallery::i18n($desc). '" '.
                                'link="'. ( $withurls ? $image->get_permalink() : ''). '" '.
                                'target="_blank" '.
                                'pause="" />'. "\n";
                }	
                
                function get_permalink($page_id) {		 
                        if ($page_id == 0)	
                                $permalink = get_option('siteurl');		 
                        else 
                                $permalink = get_permalink($page_id);
                                         
                        return $permalink;		 
                }	
                        
        }
    endif;

    // Check we have the required GET parameters
    $mode = isset ($_GET['mode']) ? $_GET['mode'] : 'gallery';
    $withurls = isset ($_GET['url']) ? true : false;
    
    // Act according to the required mode
    $rss = '';
    if ( $mode == 'gallery' ) {
                    
            // Get all galleries
            $galleries = $nggdb->find_all_galleries();
    
            if ( count($galleries) == 0 ) {
                    header('content-type:text/plain;charset=utf-8');
                    echo sprintf(__("No galleries have been yet created.","nggallery"), $gid);
                    exit;
            }
            
            // Get additional parameters
            $gid = isset ($_GET['gid']) ? (int) $_GET['gid'] : 0;
            
            //if no gid is present, take the first gallery
            if ( $gid == 0 ) {
            $first = current($galleries);
            $gid = $first->gid;
            }
                
            
            // Set the main gallery object
            $gallery = $galleries[$gid];
            
            if (!isset($gallery) || $gallery==null) {
                    header('content-type:text/plain;charset=utf-8');
                    echo sprintf(__("The gallery ID=%s does not exist.","nggallery"), intval($gid) );
                    exit;
            }
    
            $rss = nggSlidepressXML::get_gallery_sxml($gallery, $withurls);	
            
    } else if ( $mode == 'album' ) {
            
            // Get additional parameters
        $aid = isset ($_GET['aid']) ? (int) $_GET['aid'] : 0;	
            if ( $aid == 0 ) {
                    header('content-type:text/plain;charset=utf-8');
                    _e("No album ID has been provided as parameter", "nggallery");
                    exit;
            }
            
            // Get the album object
            $album = nggdb::find_album($aid);
            if (!isset($album) || $album==null ) {
                    header('content-type:text/plain;charset=utf-8');
                    echo sprintf(__("The album ID=%s does not exist.", "nggallery"), intval($aid) );
                    exit;
            }
            
            $rss = nggSlidepressXML::get_album_sxml($album, $withurls);	
    } else {
            header('content-type:text/plain;charset=utf-8');
            echo __('Invalid MediaRSS command', 'nggallery');
            exit;
    }
    
    
    // Output header for media RSS
    header("content-type:text/xml;charset=utf-8");
    echo "<?xml version='1.0' encoding='UTF-8'?>\n";
    echo $rss;

endif;

?>