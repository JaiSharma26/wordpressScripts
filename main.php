<?php

include_once('../wp-load.php');

require_once(ABSPATH . 'wp-admin/includes/media.php');
require_once(ABSPATH . 'wp-admin/includes/file.php');
require_once(ABSPATH . 'wp-admin/includes/image.php');


$csv = processCsv('perfumes.csv');	//array_map('str_getcsv', file('perfumes.csv'));

/*echo '<pre>';
print_r($csv);*/
$cat = '';
if($csv[0]['Gender'] == 'MEN'){
	 $cat = 'Mens';
} elseif($csv[0]['Gender'] == 'WOMEN') { 
	$cat = 'Womens';
} 


//echo '<pre>'; print_r($csv); echo '</pre>'; die;

$newArr = array(); //store attribute array

foreach($csv as $c) {


            $product_data['name'] = $c['Name'];
            $product_data['description'] = $c['Description'];
            $product_data['categories'] = array($cat,'Perfumes','Subscriptions');
            $product_data['sku'] = '';
            $product_data['available_attributes'] = array('PerfumeOptions');
            $product_data['variations'][0] = array('attributes' => array('RegularBuy'),'price' => $c['8ml a la carte']);
            $product_data['variations'][1] = array('attributes' => array('Subscription'),'price' => $c['8ml Subscription Price']);
            $product_data['image'] = $c['Image Large'];
            //$product_data['cost'] = $csv[0]['cost'];
            $product_data['full_size_cost'] = $c['Full Size $'];
            $product_data['cost'] = $c['Cost'];
            $product_data['MSRP'] = $c['MSRP'];
            $product_data['year_introduced'] = $c['Year Introduced'];
            $product_data['brand'] = $c['Designer'];
            $product_data['edt_edp'] = $c['EDT/EDP'];
            $product_data['Notes'] = explode(',',$c['Notes']);
            $product_data['size_ml'] = $c['Size ML'];
            $product_data['recommended'] = $c['Recommended Use'];
            $product_data['Notes'][] = $c['Recommended Use'];
            //echo '<pre>'; print_r($product_data); echo '</pre>'; die;

            //Insert product
            insert_product($product_data,$newArr);
            //break;

} //end foreach

?>

            <?php

            function processCsv($absolutePath)
            {
                $csv = array_map('str_getcsv', file($absolutePath));
                $headers = $csv[0];
                unset($csv[0]);
                $rowsWithKeys = [];
                foreach ($csv as $row) {
                    $newRow = [];
                    foreach ($headers as $k => $key) {
                        $newRow[$key] = $row[$k];
                    }
                    $rowsWithKeys[] = $newRow;
                }
                return $rowsWithKeys;
            }


            function insert_product ($product_data,$newArr)  
            {
                $post = array( // Set up the basic post data to insert for our product

                    'post_author'  => 1,
                    'post_content' => $product_data['description'],
                    'post_status'  => 'publish',
                    'post_title'   => $product_data['name'],
                    'post_parent'  => '',
                    'post_type'    => 'product'
                );

                $post_id = wp_insert_post($post); // Insert the post returning the new post id
 
                //Set Featured Image

                $media = media_sideload_image($product_data['image'], $post_id);

                if(!empty($media) && !is_wp_error($media)){
            	    $args = array(
            	        'post_type' => 'attachment',
            	        'posts_per_page' => -1,
            	        'post_status' => 'any',
            	        'post_parent' => $post_id
            	    );

            	    // reference new image to set as featured
            	    $attachments = get_posts($args);

            	    if(isset($attachments) && is_array($attachments)){
            	        foreach($attachments as $attachment){
            		            // grab source of full size images (so no 300x150 nonsense in path)
            		            $image = wp_get_attachment_image_src($attachment->ID, 'full');
            		            // determine if in the $media image we created, the string of the URL exists
            		            if(strpos($media, $image[0]) !== false){
            		                // if so, we found our image. set it as thumbnail
            		                set_post_thumbnail($post_id, $attachment->ID);
            		                // only want one image
            		                break;
            		            }
            		        }
            		    }
            	}



                if (!$post_id) // If there is no post id something has gone wrong so don't proceed
                {
                    return false;
                }

                //update custom field's value

                // echo '<pre>'; print_r($full_size_bottle_price); echo '</pre>'; die;

                $fields = get_group_fields( 'Regular Perfume Price');
                $full_size_bottle_price = $fields[0]['key'];
                $year_introduced = $fields[1]['key'];
                $brand = $fields[2]['key'];
                $cost = $fields[3]['key'];
                $msrp = $fields[4]['key'];
                $edt_edp = $fields[5]['key'];

                update_field($full_size_bottle_price, trim($product_data['full_size_cost']), $post_id);
                update_field($cost, trim($product_data['cost']), $post_id);
                update_field($msrp, trim($product_data['MSRP']), $post_id);
                update_field($year_introduced, trim($product_data['year_introduced']), $post_id);
                update_field($brand, trim($product_data['brand']), $post_id);
                update_field($edt_edp, trim($product_data['edt_edp']), $post_id);

                //update product stock

                update_post_meta( $post_id, '_stock', 9999);
                update_post_meta( $post_id, '_manage_stock', 'yes');
                update_post_meta( $post_id, '_stock_status', 'instock');

               /* $product = wc_get_product($post_id);

                echo '<pre>'; print_r($product); echo '</pre>'; die;

                wc_update_product_stock( $product, 9999, 'increase');*/

                update_post_meta($post_id, '_sku', $product_data['sku']); // Set its SKU
                update_post_meta( $post_id,'_visibility','visible'); // Set the product to visible, if not it won't show on the front end

                wp_set_object_terms($post_id, $product_data['categories'], 'product_cat'); // Set up its categories
                wp_set_object_terms($post_id, 'variable', 'product_type'); // Set it to a variable product type

                
                 insert_product_attributes2($post_id, $product_data); 
                //insert_product_attributes($post_id );     //,$product_data['available_attributes'],$product_data['variations']);
                insert_product_variations($post_id,$product_data['variations']);

                
                //insert_product_variations2($post_id,$newArr);
              }



        function insert_product_attributes($post_id) {

                $terms = get_terms("pa_perfumes-options");

                $trmVals = array();

                foreach($terms as $term) {
                    $trmVals[] = $term->name;
                }

                $attribute = 'perfumes-options';
                $product_attributes_data['pa_'.$attribute] = array( // Set this attributes array to a key to using the prefix 'pa'

                        'name'         => 'pa_'.$attribute,
                        'value'        => '',
                        'is_visible'   => '0',
                        'is_variation' => '1',
                        'is_taxonomy'  => '1'

                    );

                $product_default_data['pa_'.$attribute] = 'subscription';

                //echo '<pre>'; print_r($product_attributes_data); die;
                 wp_set_object_terms($post_id, $trmVals, 'pa_' . $attribute);
                 update_post_meta($post_id, '_product_attributes', $product_attributes_data);
                 update_post_meta($post_id, '_default_attributes', $product_default_data);
        }




              // add attribute from csv file

              function insert_product_attributes2($post_id,$product_data) {

                            $attr_tax = wc_get_attribute_taxonomies();        //get all texonomies;

                            $term_tex = array();

                            $notesArr = array();

                            foreach($product_data['Notes'] as $notes) {

                                $notesArr[] = strtolower(trim($notes));

                            }

                            foreach($attr_tax as $tex) {

                                // echo $tex->attribute_name; die;

                                $terms = get_terms("pa_".$tex->attribute_name,array("hide_empty" => false) );


                                foreach($terms as $term) {

                                    $term_tex[$tex->attribute_name][] = strtolower($term->name);

                                } //endforeach

                            foreach($term_tex as $key => $ttex) {

                              foreach($notesArr as $nArr) {

                                  if(in_array($nArr,$ttex)) {

                                        $newArr[$key][] = $nArr;

                                  }

                              } //endforeach

                            } //endforeach

                           //  echo '<pre>'; print_r($newArr); echo '</pre>';

                             $newArr['perfumes-options'] = '';

                             $terms = get_terms("pa_perfumes-options");

                            $trmVals = array();

                            foreach($terms as $term) {
                                $newArr['perfumes-options'][] = $term->name;
                            }

                            // echo '<pre>'; print_r($newArr); die;

                            foreach($newArr as $key => $n_arr) {

                                     $product_attributes_data['pa_'.$key] = array( // Set this attributes array to a key to using the prefix 'pa'

                                            'name'         => 'pa_'.$key,
                                            'value'        => '',
                                            'is_visible'   => '0',
                                            'is_variation' => ($key == 'perfumes-options') ? '1' : '0',
                                            'is_taxonomy'  => '1'

                                        );


                                wp_set_object_terms($post_id, $n_arr, 'pa_' . $key);
                                update_post_meta($post_id, '_product_attributes', $product_attributes_data);
                                if ($key == 'perfumes-options') {
                                    $product_default_data['pa_'.$key] = 'subscription';
                                   update_post_meta($post_id, '_default_attributes', $product_default_data);
                                }

                            } //endforeach


                          }
                         
                }

              function insert_product_variations ($post_id, $variations)  {

               /* echo '<pre>'; 
                print_r($variations); die;
            */
                 foreach ($variations as $index => $variation)
                {

                    $variation_post = array( // Setup the post data for the variation

                        'post_title'  => 'Variation #'.$index.' of '.count($variations).' for product#'. $post_id,
                        'post_name'   => 'product-'.$post_id.'-variation-'.$index,
                        'post_status' => 'publish',
                        'post_parent' => $post_id,
                        'post_type'   => 'product_variation',
                        'guid'        => home_url() . '/?product_variation=product-' . $post_id . '-variation-' . $index
                    );

                   $variation_post_id = wp_insert_post($variation_post); // Insert the variation
                    
                           foreach ($variation['attributes'] as $value) {

                                $attribute_term = get_term_by('name', $value, 'pa_perfumes-options'); // We need to insert the slug not the name into the variation post meta

                                update_post_meta($variation_post_id, 'attribute_pa_perfumes-options', $attribute_term->slug);  
                                //update_post_meta($post_id,'_default_attributes', 'subscription');  

                            }
                    
                            update_post_meta($variation_post_id, '_price', $variation['price']);
                            update_post_meta($variation_post_id, '_regular_price', $variation['price']);                       
             
                    }

                    

              }


            function insert_product_variations2($post_id, $variations)  {

                 foreach ($variations as $index => $variation)
                {

                    $variation_post = array( // Setup the post data for the variation

                        'post_title'  => 'Variation #'.$index.' of '.count($variations).' for product#'. $post_id,
                        'post_name'   => 'product-'.$post_id.'-variation-'.$index,
                        'post_status' => 'publish',
                        'post_parent' => $post_id,
                        'post_type'   => 'product_variation',
                        'guid'        => home_url() . '/?product_variation=product-' . $post_id . '-variation-' . $index
                    );

                   $variation_post_id = wp_insert_post($variation_post); // Insert the variation
                    
                           foreach ($variation as $value) {

                                $attribute_term = get_term_by('name', $value, 'pa_'.$index); // We need to insert the slug not the name into the variation post meta

                                update_post_meta($variation_post_id, 'attribute_pa_'.$index, $attribute_term->slug);  
                                //update_post_meta($post_id,'_default_attributes', 'subscription');  

                            }
             
                    }

              }              


?>