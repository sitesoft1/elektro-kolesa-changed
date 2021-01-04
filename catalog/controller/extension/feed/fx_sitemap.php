<?php
class ControllerExtensionFeedFxSitemap extends Controller {

    private $host = '';
    private $excluded = array();
    private $settings = array();
    private $get_arr = array();
    private $categories = false;
    private $mode = '';
    private $report = '';
	
	public function index() {
		
		$cat = '';
		
		$this->settings = $settings = json_decode($this->config->get('fx_sitemap_settings'), true);
		
		if (!$settings['sitemap_on']) exit;
		
		$this->load->model('extension/module/fx_sitemap');		
		
		if ($settings['multistore']) $this->model_extension_module_fx_sitemap->isMulti(true);
		
		$host = $this->config->get('config_secure') ? str_replace('http:/', 'https:/', $this->config->get('config_ssl')) : $this->config->get('config_url');
		$ssl = $this->config->get('config_secure') ? 'https://' : 'http://';
		
		/*if (substr_count($host, "/") > 3){		
			$ssl = $this->config->get('config_secure') ? 'https://' : 'http://';
			$host = $ssl . $this->request->server['HTTP_HOST'];
		}*/
		
		
		$config_language_id = $this->config->get('config_language_id');
		
		$language_id = isset($this->request->get['language']) ? $this->request->get['language'] : (int)$config_language_id;
		
		$this->config->set('config_language_id', $language_id);
		
		$host = str_replace('index.php?route=common/home', '', $this->url->link('common/home', '', 'SSL'));
		
		if (isset($this->request->get['host'])) $host = $this->request->get['host'];
		
		$prefix = isset($this->request->get['prefix']) ? $this->request->get['prefix'] : '';
		
		$this->host = $host = rtrim($host, '/') . '/' . $prefix;
		
        $get_arrs = array();
        
        if ($settings['default']){
            $parse = parse_url(str_replace('&amp;', '&', $settings['default']));          
            parse_str($parse['query'], $get_arrs);
        }
		
		$get_arr = array();
		
		$arrs = array('limit', 'page', 'part', 'express', 'express_cat', 'ultra', 'file', 'key', 'save', 'multi', 'img', 'blog', 'article', 'news', 'language', 'prefix', 'host', 'cat_prefix', 'prod_prefix', 'man_prefix', 'amp');
		
		foreach ($arrs as $g) {
			if (isset($get_arrs[$g])){
				$get_arr[$g] = $get_arrs[$g];
			}
			if (isset($this->request->get[$g])){
				$get_arr[$g] = $this->request->get[$g];
				if ($this->request->get[$g] == 'off') unset($get_arr[$g]);
			}
			
		}
		
		if (isset($this->request->get['page'])){
			$get_arr['part'] = $this->request->get['page'];
		}
		if (isset($this->request->get['part']) || isset($this->request->get['page'])){
			unset($get_arr['multi']);
			$settings['multi'] = false;
			
		}
		if (isset($this->request->get['multi']) && $this->request->get['multi'] == 'off'){
			$get_arr['multi'] = false;
		}
		
		$this->get_arr = $get_arr;
		
		$this->settings = $settings = array_merge($settings, $get_arr);
		
		$mode = $settings['express'] ? 'express' : ($settings['ultra'] ? 'ultra' : '');		
		
		$this->load->model('extension/module/fx_sitemap');
		
		$this->model_extension_module_fx_sitemap->lang($language_id);
		
		$mode = $settings['express'] ? 'express' : ($settings['ultra'] ? 'ultra' : '');		
		
		
		$excluded = $this->excluded = $settings['exclude_file_on'] ? $this->check("delete.sitemap") : array();
		$plus  =  $settings['add_file_on'] ? $this->check("add.sitemap") : array();
		
		$ex = !empty($this->excluded) ? $this->excluded[0] : '';
		
		if ($ex == 'db:hpmodel_product_hidden' || $ex == 'db:kjseries_product_hidden' || $ex == 'db:hidden'){
			$excluded = array();
			$this->model_extension_module_fx_sitemap->db_exclude($ex);
		}
		
		$start_time = (time() + (float)microtime());
		
		$total = $total_p = 0;

		$url = '';
		
		if ($settings['multi'] || !isset($settings['part']) || (isset($settings['part']) && (int)$settings['part'] == 0)){
		
			if ($settings['products_on']){
				if (isset($settings['img'])){
					$total_p =  $settings['img'] == 1 ? $this->model_extension_module_fx_sitemap->getProductsTotal() : $this->model_extension_module_fx_sitemap->getImgTotal();
					$url .= '&amp;img='. (int)$settings['img'];
				}else{
					$total_p = $this->model_extension_module_fx_sitemap->getProductsTotal();
				}
			}

			$limit = (isset($settings['limit']) && ((int)$settings['limit'] > 0)) ? (int)$settings['limit'] : 47999;
			//if ($total_p > $limit) {
				$total += $total_c = !$settings['categories_on'] ? 0 : $this->model_extension_module_fx_sitemap->getCategoriesTotal();
				$total += $total_m = !$settings['brands_on'] ? 0 : $this->model_extension_module_fx_sitemap->getManufacturersTotal();
				$total += $total_m = !$settings['brands_on'] ? 0 : $this->model_extension_module_fx_sitemap->getManCatDBTotal();
				$total += !$settings['news_on'] ? 0 : $this->model_extension_module_fx_sitemap->getAllNewsTotal();
				$total += !$settings['blog_on'] ? 0 : $this->model_extension_module_fx_sitemap->getAllBlogTotal();
				$total += !$settings['mfp_on'] ? 0 : $this->model_extension_module_fx_sitemap->getMFPTotal();
				$total += !$settings['ocfilter_on'] ? 0 : $this->model_extension_module_fx_sitemap->getOCFilterTotal();
				$total += !$settings['vier_on'] ? 0 : $this->model_extension_module_fx_sitemap->getVierTotal();
				$total += !$settings['informations_on'] ? 0 : $this->model_extension_module_fx_sitemap->getInformationsTotal();
				$total += !$settings['records_on'] ? 0 : $this->model_extension_module_fx_sitemap->getCMSBlogTotal();
				$total += !$settings['article_on'] ? 0 : $this->model_extension_module_fx_sitemap->getArticlesTotal($settings['article_route'] == 'octemplates/blog_article' ? 'oct_blog_' : '');
				$total += !$settings['add_file_on'] ? 0 : count($plus);
				$total += !$settings['exclude_file_on'] ? 0 : -count($plus);
				$total += !$settings['home_on'] ? 0 :1;
				$total += !$settings['special_on'] ? 0 :1;
				$total += !$settings['contact_on'] ? 0 :1;
			//}
			
		}		

		if ($settings['multi']){
			
			unset($settings['multi']);
			
			$m_arrs = array('limit', 'express', 'express_cat', 'ultra', 'file', 'key', 'save', 'img', 'blog', 'article', 'news', 'language', 'prefix', 'host', 'cat_prefix', 'prod_prefix', 'man_prefix', 'amp');

			$limit = (isset($settings['limit']) && ((int)$settings['limit'] > 0)) ? (int)$settings['limit'] : 47999;
			
			if (isset($get_arr['limit'])){
				$url .= '&amp;limit='. (int)$settings['limit'];
			}
			
			foreach ($m_arrs as $g) {
				if (isset($this->request->get[$g]))	$url .= '&amp;'. $g .'='. $this->request->get[$g];
			}
			
			$file_access = false;
			
			if(isset($settings['key']) && ($settings['key'] == $settings['key'])) $file_access = true;				
			if(!$settings['key']) $file_access = true;
			
			$output = '<?xml version="1.0" encoding="utf-8"?>';
			
			$output = '<?xml version="1.0" encoding="UTF-8"?><?xml-stylesheet type="text/xsl" href="../catalog/view/theme/default/stylesheet/fx_sitemap.xsl"?>';
			
			$output .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
		
			if (($total + $total_p) > $limit) {
				$num = ceil($total / $limit);
				$num_p = ceil($total_p / $limit);
				$n = 1;
				
				do {
					if ($total == 0) continue;
					$go = $host . 'index.php?route=extension/feed/fx_sitemap&amp;part=0.' . $n . str_replace("{n}", $n, $url);					
					$file = isset($settings['file']) ? $host . $settings['file']. '0.' . $n.'.xml' : $go;
					
					$output .= '<sitemap>';
					$output .= '<loc>' . $file . '</loc>';
					$output .= '</sitemap>';
					
					if (isset($settings['file']) && isset($settings['save']) && $file_access){
						$out = $this->cache->get('fx.sitemap.0.' . $n);
						if (!$out) {
							$out = $this->goSitemap($go);
							$this->cache->set('fx.sitemap.0.' . $n, $out);
						} else {							
							file_put_contents($settings['file'].'0.' . $n .'.xml', $out);
						}
					}
					
					$n++;
				} while ($n <= $num);
				
				$n = 1;
				
				do {
					if ($total_p == 0) continue;
					$go = $host . 'index.php?route=extension/feed/fx_sitemap&amp;part=' . $n . str_replace("{n}", $n, $url);					
					$file = isset($settings['file']) ? $host . $settings['file'].$n.'.xml' : $go;
					
					$output .= '<sitemap>';
					$output .= '<loc>' . $file . '</loc>';
					$output .= '</sitemap>';
					
					if (isset($settings['file']) && isset($settings['save']) && $file_access){
						$out = $this->cache->get('fx.sitemap.' . $n);
						if (!$out) {
							$out = $this->goSitemap($go);
							$this->cache->set('fx.sitemap.' . $n, $out);
						} else {							
							file_put_contents($settings['file']. $n .'.xml', $out);
						}
					}
					
					$n++;
				} while ($n <= $num_p);

			}else{
				$url .= '&amp;multi=off';
				$go = $host . 'index.php?route=extension/feed/fx_sitemap' . $url;					
				$file = isset($settings['file']) ? $host . $settings['file'].'.xml' : $go;
				
				$output .= '<sitemap>';
				$output .= '<loc>' . $file . '</loc>';
				$output .= '</sitemap>';
				
				if (isset($settings['file']) && isset($settings['save']) && $file_access){
					$out = $this->goSitemap($go);
				}
			}

				$output .= '</sitemapindex>';
				
				$this->response->addHeader(empty($settings['http_header']) ? 'Content-Type: application/xml' : $settings['http_header']);
				$this->response->setOutput($output);

			
		}else{
			
			$output  = '<?xml version="1.0" encoding="UTF-8"?>';
			$output = '<?xml version="1.0" encoding="UTF-8"?><?xml-stylesheet type="text/xsl" href="../catalog/view/theme/default/stylesheet/fx_sitemap.xsl"?>';
			if (!isset($settings['img'])){
				$output .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
			}else{
				$output .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">';
			}
			//	$output = $output . '<url><loc>'. $host .'</loc><changefreq>daily</changefreq><priority>1</priority></url>'; //главная
			
			$data['limit'] = $settings['limit'] ? (int)$settings['limit'] : 49499;
			
			$data['start'] = isset($settings['part']) ? ((int)$settings['part'] - 1) * (int)$data['limit'] : 0;
			
			if ($data['start'] < 0) $data['start'] = 0;
			
			$out = array();

			if ($settings['product_changefreq'] == 'off') $settings['product_changefreq'] = '';
			if ($settings['category_changefreq'] == 'off') $settings['category_changefreq'] = '';
			if ($settings['brands_changefreq'] == 'off') $settings['brands_changefreq'] = '';
			
			
			if ($settings['products_on'] && !((isset($settings['part'])) && (float)$settings['part'] < 1)){  ///// PRODUCTS
			
				if ($mode == 'express' || $this->settings['multilang']){   /////  EXPRESS
				
					$products = $this->model_extension_module_fx_sitemap->getProductsExpress($data);
					
					$date = '';					
					
					$prod_prefix = isset($this->request->get['prod_prefix']) ? $this->request->get['prod_prefix'] : $prefix;						
					$prod_host = str_replace($prefix, $prod_prefix, $host);
					
					foreach ($products as $product) {
						$url = $prod_host;
						
						if (!isset($product['keyword'])) {
							$url .= 'index.php?route=product/product&amp;product_id=' . $product['product_id'];					
						}else {						
							//$url .= $product['keyword'] . $settings['postfix'];
							$url .= 'goods/' . $product['keyword'] . $settings['postfix'];
						}
						
						if ($settings['product_lastmod']) $date = (int)$product['date_modified'] > 2000 ? substr($product['date_modified'], 0, 10) : '';
						
						$output .= $this->xml($url, $date, $settings['product_changefreq'], $settings['product_priority']);
					}
					
				}else if ($mode == 'ultra'){   /////  ULTRA
				
					$this->cacher();
					
					$products = $this->model_extension_module_fx_sitemap->getProductsUltra($data);
					
					$date = '';
					
					$categories_cache = $this->categories;
					
					foreach ($products as $product) {
						
						$category_url = $product_url = '';
						
						$in_category = !empty($product['category_id']) && isset($categories_cache[$product['category_id']]);
						
						$short = !empty($product['keyword']) && !$in_category;						
						
						$is_seo_url = $short || (!empty($product['keyword']) && $in_category && $categories_cache[$product['category_id']]['is_seo']);

						if (!$is_seo_url){ 
							$category_url = 'index.php?route=product/product&amp;product_id=';
							if ($in_category) $category_url = 'index.php?route=product/product&amp;path=' . $product['category_id'] . '&amp;product_id=';
							$product_url = $product['product_id'];
						} else {
							if (!isset($cat_slash)) $cat_slash = substr($categories_cache[$product['category_id']]['url'], -1) == '/' ? '' : '/';
							$category_url = $short ? '' : $categories_cache[$product['category_id']]['url'] . $cat_slash;
							$product_url = $product['keyword'] . $settings['postfix'];					
						}
						
						$url = $host . $category_url . $product_url;
						
						if ($settings['product_lastmod']) $date = (int)$product['date_modified'] > 2000 ? substr($product['date_modified'], 0, 10) : '';
						
						$output .= $this->xml($url, $date, $settings['product_changefreq'], $settings['product_priority']);
					}
					
				}else{   /////  NORMAL
				
					if ( isset($settings['img']) && ($settings['img'] == 1) ){
						$products = $this->model_extension_module_fx_sitemap->getImgOne($data);
					}elseif (isset($settings['img'])){
						$products = $this->model_extension_module_fx_sitemap->getImg($data);
					}else{
						$products = $this->model_extension_module_fx_sitemap->getProducts($data);
					}
					
					$date = '';
					
					foreach ($products as $product) {
					
						if ($settings['product_lastmod']){
					
							$date = date_format( new DateTime($product['date_modified']), 'Y-m-d');
							if ($date == '-0001-11-30') $date = date_format( new DateTime($product['date_added']), 'Y-m-d'); 
							if ($date == '-0001-11-30') $date = ''; 
						
						}
						
						$url= $this->url->link('product/product', 'product_id=' . $product['product_id'], 'SSL');
						
						if (isset($settings['amp'])) $url = str_replace("&product_id=", "&amp;product_id=", $url);
						
						$output .= $this->xml($url, $date, $settings['product_changefreq'], $settings['product_priority'], isset($settings['img']) ? $product : array());

					}
				}
			}
		
			if (!isset($settings['part']) || ((int)$settings['part'] == 0)) {
				
				$subpart = 1;
				
				$out = array();
				
				$i = 1;
				
				$tp = (!isset($settings['part']) && isset($total_p)) ? $total_p : 0;
				
				if (isset($settings['part'])) $subpart = (int)(str_replace('0.', '', (string)$settings['part']));
				$subpart = $subpart ? $subpart : 1;
				
				$limit = $data['limit'] = (int)$settings['limit'] > 0 ? (int)$settings['limit'] : 49499;
			
				$start = $data['start'] = ($subpart - 1) * $data['limit'];
				
				$max = isset($settings['part']) ? $data['start'] + $data['limit'] : $limit;
				
				//$i = $start > 0 ? $start : 1;
				
				
				$cat_prefix = isset($this->request->get['cat_prefix']) ? $this->request->get['cat_prefix'] : $prefix;						
				$cat_host = str_replace($prefix, $cat_prefix, $host);
				
				while ($i + $tp <= $max){

					$mode = ($mode == 'express') ? '' : $mode;
					if ($this->settings['multilang'] || $this->settings['express_cat']) $mode = 'express';
					
					if ($settings['categories_on']){ /////  CATEGORIES ADD					
					
						if ($mode == 'express' || $mode == 'ultra') {  /////  ULTRA/EXPRESS
						
							$this->cacher();							
							unset($this->categories[0]); //array_splice($this->categories, 0, $i);						
						
							foreach ($this->categories as $category){
								if ($i > $start){
									$short = $category['url'];
									$url = $cat_host . (!$category['is_seo'] ? 'index.php?route=product/category&amp;path' . $short : $short);
									
									$date = $settings['category_lastmod'] ? $category['date_modified'] : '';
									
									$out[$i] = $this->xml($url, $date, $settings['category_changefreq'], $settings['category_priority']);
								}
								$i++; if ($i > $max) break(2);								
							}
						
						} else {  /////  NORMAL
						
							$categories = $this->model_extension_module_fx_sitemap->getCategoriesStore();
							
							foreach ($categories as $category){
								
								if ($i > $start){
								
									$url = $this->url->link('product/category', 'path=' . $category['category_id'], 'SSL');
									
									if (isset($settings['amp'])) $url = str_replace("&path=", "&amp;path=", $url);
									
									$date = '';
									
									if ($settings['category_lastmod']){								
										$date = date_format( new DateTime($category['date_modified']), 'Y-m-d');
										if ($date == '-0001-11-30') $date = date_format( new DateTime($category['date_added']), 'Y-m-d');
										if ($date == '-0001-11-30') $date = '';									
									}
									
									$out[$i] = $this->xml($url, $date, $settings['category_changefreq'], $settings['category_priority']);
								}
								$i++; if ($i > $max) break(2);								
							}
							
							
						}
					}
					
					
					
					if ($settings['brands_on']){					
						$manufacturers = ($mode == 'express' || $mode == 'ultra') ? $this->model_extension_module_fx_sitemap->getManufacturersExpress() : $this->model_extension_module_fx_sitemap->getManufacturers();						
						
						$man_prefix = isset($this->request->get['man_prefix']) ? $this->request->get['man_prefix'] : $prefix;						
						$man_host = str_replace($prefix, $man_prefix, $host);
						
						foreach ($manufacturers as $manufacturer) {
							if ($i > $start){
								$url= isset($manufacturer['keyword']) ? $man_host . $manufacturer['keyword'] . $settings['postfix'] : $this->url->link('product/manufacturer/info', 'manufacturer_id=' . $manufacturer['manufacturer_id'], 'SSL');
								
								if (isset($settings['amp'])) $url = str_replace("&manufacturer_id=", "&amp;manufacturer_id=", $url);
								
								$out[$i] = $this->xml($url, '', $settings['brands_changefreq'], $settings['brands_priority']);
							}
							$i++; if ($i > $max) break(2);
						}
					}
					
					
					if ($settings['informations_on']){
						$informations = ($mode == 'express' || $mode == 'ultra') ? $this->model_extension_module_fx_sitemap->getInformationsExpress() : $this->model_extension_module_fx_sitemap->getInformations();
						foreach ($informations as $information) {
							if ($i > $start){
								$url= isset($information['keyword']) ? $host . $information['keyword'] . $settings['postfix'] : $this->url->link('information/information', 'information_id=' . $information['information_id'], 'SSL');
								
								if (isset($settings['amp'])) $url = str_replace("&information_id=", "&amp;information_id=", $url);
								
								$out[$i] = $this->xml($url);
							}
							$i++; if ($i > $max) break(2);
						}
					}
					
					
					if ($settings['blog_on']){
						
						$route = $settings['records_on'] ? 'record/blog' : $settings['blog_route'];
						$blog = empty($route) ? array() : $this->model_extension_module_fx_sitemap->getAllBlog();
						foreach ($blog as $new) {
							if ($i > $start){
								$url= str_replace("&blog", "&amp;blog", $this->url->link( $route, 'blog_id=' . $new['blog_id'], 'SSL'));
								$out[$i] = $this->xml($url); 
							}
							$i++; if ($i > $max) break(2);
						}
					}
					
					if ($settings['news_on']){
						
						$route = $settings['news_route'];
						$news = empty($route) ? array() : $this->model_extension_module_fx_sitemap->getAllNews();
						foreach ($news as $new) {
							if ($i > $start){
								$url= str_replace("&new", "&amp;new", $this->url->link( $route, 'news_id=' . $new['news_id'], 'SSL'));
								$out[$i] = $this->xml($url);
							}
							$i++; if ($i > $max) break(2);
						}
					}
					
					if ($settings['article_on']){					
						$route = $settings['article_route'];
						
						$oct = $route == 'octemplates/blog_article' ? 'oct_blog_' : '';
						
						$articles = $this->model_extension_module_fx_sitemap->getArticles($oct);
						
						foreach ($articles as $new) {
							if ($i > $start){
								$url= str_replace("&article", "&amp;article", $this->url->link( $route, $oct . 'article_id=' . $new['article_id'], 'SSL'));
								$out[$i] = $this->xml($url);
							}
							$i++; if ($i > $max) break(2);
						}					
					}
					
					if ($settings['records_on']){
						
						$blog = $this->model_extension_module_fx_sitemap->getCMSBlog();
						$route = 'record/record' ;
						foreach ($blog as $new) {
							if ($i > $start){					
								$url = str_replace("&record", "&amp;record", $this->url->link( $route, 'record_id=' . $new['record_id'], 'SSL'));						
								$date = (int)$new['date_modified'] > 2000 ? substr($new['date_modified'], 0, 10) : '';
								$out[$i] = $this->xml($url, $date);
							}
							$i++; if ($i > $max) break(2);
						}
					}					
					
					if ($settings['home_on'] || $settings['contact_on'] || $settings['special_on']){
					
						$pages = $this->model_extension_module_fx_sitemap->getPages();
												
						if ($settings['home_on'] && $i > $start){
							$url = $this->url->link('common/home', '', 'SSL');
							$date = $pages['home']['date'];
							$out[$i] = $this->xml($url, $date, 'weekly', '1.0');
							$i++;
						}
						
						if ($settings['special_on'] && $i > $start){
							$url = $this->url->link('product/special', '', 'SSL');
							$date = $pages['special']['date'];
							$out[$i] = $this->xml($url, $date, 'weekly', '0.8');
							$i++;
						}
						
						if ($settings['contact_on'] && $i > $start){
							$url = $this->url->link('information/contact', '', 'SSL');
							$date = $pages['contact']['date'];
							$out[$i] = $this->xml($url, $date, 'monthly', '0.6');
							$i++;
						}
					
					}					
					
					if ($settings['brands_category_on']){
						$categories_m = $this->model_extension_module_fx_sitemap->getManCatDB();
						foreach ($categories_m as $category) {
							$manufacturers = explode(',', $category['manufacturers']);
							foreach ($manufacturers as $manufacturer) {
								if ($i > $start){
									$url = $this->url->link('product/category', 'path=' . $category['category_id'] . '&manufacturer_id=' . $manufacturer);
									$out[$i] = $this->xml($url, '', 'monthly', '0.5');
								}								
								$i++; if ($i > $max) break(3);
							}
						}
					}
					
					$slash = $settings['slash'] ? '/' : '';
					
					if ($settings['mfp_on']){
						$mfp = $this->model_extension_module_fx_sitemap->getMFP();
						foreach ($mfp as $filter) {
							if ($i > $start){
								if (!isset($mfp_slash)) $mfp_slash = substr($filter['path'], -1) == '/' ? '' : '/';
								$url = $host . $filter['path'] . $mfp_slash . $filter['alias'] . $slash;
								$out[$i] = $this->xml($url);
							}								
							$i++; if ($i > $max) break(2);
						}
					}
					
					if ($settings['vier_on']){
						$vier = $this->model_extension_module_fx_sitemap->getVier();
						foreach ($vier as $filter) {
							if ($i > $start){								
								$url = $host . ltrim($filter['link'], '/');
								$out[$i] = $this->xml($url);
							}								
							$i++; if ($i > $max) break(2);
						}
					}
					
					if ($settings['ocfilter_on']){
						$ocf = $this->model_extension_module_fx_sitemap->getOCFilter();
						$this->cacher();
						$categories_cache = $this->categories;
						foreach ($ocf as $filter) {
							if ($i > $start){								
								if (isset($categories_cache[$filter['category_id']]) && $categories_cache[$filter['category_id']]['is_seo']){
									if (!isset($ocf_slash)) $ocf_slash = substr($categories_cache[$filter['category_id']]['url'], -1) == '/' ? '' : '/';
									
									$keyword = $filter['keyword'] ? $filter['keyword'] : $filter['params'];
									
									$url = $host . $categories_cache[$filter['category_id']]['url'] . $ocf_slash . $keyword . $slash;
									
									$out[$i] = $this->xml($url);
								}
							}
							$i++; if ($i > $max) break(2);
						}
					}

					if ($settings['filterpro_on']){				
						$fp = $this->model_extension_module_fx_sitemap->getFilterPro();
						foreach ($fp as $new) {
							if ($i > $start){
								$fdata = unserialize($new['data']);
								parse_str(str_replace('&amp;', '&', $fdata['url']), $un_data);
								if (isset($un_data['route'])){
									if($un_data['route'] == 'product/category') {
										$url = $this->url->link($un_data['route'], 'path=' . (isset($un_data['path']) ? $un_data['path'] : $un_data['category_id']) . '&' . $new['url']);
									} else if(strpos($un_data['route'], 'product/manufacturer/') !== false) {
										$url = $this->url->link($un_data['route'], 'manufacturer_id=' . $un_data['manufacturer_id'] . '&' . $new['url']);
									} else {
										$url = $this->url->link($un_data['route'], $new['url']);
									}
									
									$out[$i] = $this->xml($url);
								}
							}
							$i++; if ($i > $max) break(2);
						}
					}
					
					if ($settings['add_file_on']){	   ////// ADD			
						foreach ($plus as $p) {
							if ($i > $start) $out[$i] = $this->xml(str_replace("&", "&amp;", $p));
							$i++; if ($i > $max) break(2);
						}
					}
					
					break;
				}
			}
			
			$lines = $out;
			
			foreach ($lines as $line) {
				$output = $output . $line;
			}
			
			$plug = '';
			
			if (isset($settings['part'])){ 
				$part_num = (int)str_replace('0.', '', $settings['part'], $n);
				
				$part_calc = ($n ? $total : $total_p) / $data['limit'];
				
				if ($part_calc < $part_num && !strpos($output, '</url>')) $plug = '<url><loc>'. $host .'</loc></url>';
			}			
			
			$output .= $plug;
			
			$output = $output . '</urlset>';
			
			$file_access = false;						
			if(isset($this->request->get['key']) && ($settings['key'] == $this->request->get['key'])) $file_access = true;							
			if(!$settings['key']) $file_access = true;
			
			
			$this->config->set('config_language_id', $config_language_id);
			if($settings['log']){
			
				$ip = isset($this->request->server['REMOTE_ADDR']) ? $this->request->server['REMOTE_ADDR'] : "N/A";
				$ua = isset($this->request->server['HTTP_USER_AGENT']) ? $this->request->server['HTTP_USER_AGENT'] : "N/A";
				$uri = isset($this->request->server['REQUEST_URI']) ? $this->request->server['REQUEST_URI'] : "N/A";
				
				if (strpos($ua, 'Googlebot')) $ua = 'Google Bot';
				if (strpos($ua, 'YandexBot')) $ua = 'Yandex Bot';
				if (strpos($ua, 'Mail.RU_Bot')) $ua = 'Mail.RU Bot';
				if (strpos($ua, 'bingbot')) $ua = 'Bing Bot';
				
				$end = (time() + (float)microtime());
				$time = round($end - $start_time, 5)*1000;

				file_put_contents(DIR_LOGS.'fx_sitemap.log', ("[" . date("d.m.Y H:i") . "] " . $ip . " : " . $ua . "\n" . $time . "мс  | " . $uri. "\n ------------ \n"), FILE_APPEND);
			}
			
			if (isset($settings['file'])){
				if ($file_access){
					file_put_contents(str_replace("{n}", '', $settings['file']).'.xml', $output);
				}else{
					echo('<h1 style="position: absolute; top:50%; left: 50%">Access Denied<h1>');					
					exit;
				}
				
			}
			
			$this->response->addHeader(empty($settings['http_header']) ? 'Content-Type: application/xml' : $settings['http_header']);
			
			$this->response->setOutput($output);			
		}
		
	}

	protected function goSitemap($url){
	
		//$pagecode = file_get_contents($url);/*  УБРАТЬ // В НАЧАЛЕ
		$headers = array('HTTP_ACCEPT: Something', 'HTTP_ACCEPT_LANGUAGE: ru, en, da, nl', 'HTTP_CONNECTION: Something');
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.1 (compatible; MSIE 6.0; Windows NT 5.1; FX Sitemap)');
		curl_setopt($ch, CURLOPT_URL, str_replace("&amp;", "&", $url));
		$pagecode = curl_exec( $ch );
		curl_close($ch);//*/
		return $pagecode;
	}
	
	protected function addhost($url, $host){
		$outurl = $host.$url;
		return $outurl;
	}
		
	protected function check($var){
		
		if (!file_exists(DIR_CONFIG.$var)) return array();
		
		$arr = mb_split(PHP_EOL, file_get_contents(DIR_CONFIG.$var));
		
		foreach ($arr as $key=>&$value) {
			$value = trim($value);
			if (strlen($value) < 7) unset($arr[$key]);
		}
		
		return $arr;
	}
	
	protected function getCategoriesDB($express = false) {
		
		$results = $this->model_extension_module_fx_sitemap->getCatDB();
		
		$output ='';
		
		$urls = array();
		
		foreach ($results as $result) {

			$date = $urls[$result['category_id']]['date_modified'] = $result['date_modified'];
			
			$url = $urls[$result['category_id']]['url'] = $result['url'];
			
			$is_seo = $urls[$result['category_id']]['is_seo'] = $result['is_seo'];
			
			//$output .= $this->xml($this->host . $url, $date);
		}
		
		$this->categories = $urls;
		
		return $output;
	}
	
	protected function getCategoriesExpress(){
		
		$categories = $this->model_extension_module_fx_sitemap->getCategoriesExpress();
		
		foreach ($categories as $category) {										
			if (!isset($category['keyword'])) {						
				$urls[$category['category_id']]['url'] = '=' . $category['category_id'];
				$urls[$category['category_id']]['is_seo'] = 0;
			}else {						
				$urls[$category['category_id']]['url'] = $category['keyword'];
				$urls[$category['category_id']]['is_seo'] = 1;
			}
			
			$urls[$category['category_id']]['date_modified'] = (int)$category['date_modified'] > 2000 ? substr($category['date_modified'], 0, 10) : '';
		}
		
		$this->categories = $urls;
		
		return $urls;
	}
	
	protected function xml($url, $date = '', $changefreq = 'weekly', $priority = '0.7', $data = array()) {
		
		if ($this->settings['only_seo_url'] && strpos($url, '=')) return '';
		
		if ($this->settings['exclude_file_on'] && in_array($url, $this->excluded)) return '';

		$output = '<url>';
		$output .= '<loc>' . $url . '</loc>';
		$output .= $date ? '<lastmod>' . $date . '</lastmod>' : '';
		$output .= $changefreq ? '<changefreq>' . $changefreq . '</changefreq>' : '';
		$output .= $priority ? '<priority>' . $priority . '</priority>' : '';
		
		if (!empty($data) && isset($data['image']) && !empty($data['image'])){
			$temp = explode(",", $data['image']);
			$name = htmlspecialchars($data['name'], ENT_QUOTES);
			foreach ($temp as $imgage) {
				$output .= '<image:image>';
				$output .= '<image:loc>' . $this->host . 'image/' . $imgage .'</image:loc>';
				$output .= '<image:caption>' . $name . '</image:caption>';
				$output .= '<image:title>' . $name . '</image:title>';
				$output .= '</image:image>';
			}
		}
		
		$output .= '</url>';
		
		return $output;
	}

	
	public function cacher() {
		
		if ($this->categories) return true;

		if ($this->settings['multilang'] || $this->settings['express_cat']) {
			$this->getCategoriesExpress(); /////  EXPRESS
			return true;			
		}
		
		if ($this->settings['categories_from_db'] && $this->settings['ultra']){
			$this->getCategoriesDB();
			return true;	
		}		
		
		$urls_cache = $this->cache->get('fx_sitemap4.categories.' . $this->config->get('config_language_id'));

		$this->categories = $urls_cache;
		
		if ($urls_cache) return true;		
		
		$start = (time() + (float)microtime());
		
		$this->load->model('extension/module/fx_sitemap');		
		
		$results = $this->model_extension_module_fx_sitemap->getCategoriesLite();
		
		$cat_cache = array(0 => array('url' => '/'));

		foreach ($results as $result) {	
		
			$date = (int)$result['date_modified'] > 2000 ? substr($result['date_modified'], 0, 10) : '';
		
			if((float)VERSION >= 4){ //потом 
	
				$this->load->model('localisation/language');
				$languages = $this->model_localisation_language->getLanguages();
				
				foreach ($languages as $language) {					
					
					//$this->model_extension_module_fx_sitemap->lang($language['language_id']);
					
					$p = $this->url->link('product/category', ((float)VERSION >= 3.1 ? 'language=' . $language['language_id'] . '&' : '') . 'path=' . $result['category_id']);
					
					$parts = parse_url($p);
					
					$url = ltrim($parts['path'], '/') . (isset($parts['query']) ? '?' . $parts['query'] : '');
					$url = str_replace('index.php?route=product/category&amp;path', '', $url, $no_seo);
					
					$is_seo = $no_seo ? 0 : 1;
					
					$data[] = array(
						'category_id' => $result['category_id'],
						'url' => $url,
						'is_seo' => $is_seo,
						'store_id' => $result['store_id'],
						'date_modified' => $date,
						'language_id' => $language['language_id']
					);
				}
			} else {
				
				$p = $this->url->link('product/category', 'path=' . $result['category_id']);
				
				if (isset($settings['amp'])) $p = str_replace("&path=", "&amp;path=", $p);
				
				/*$p = str_replace('://', '', $this->url->link('product/category', 'path=' . $result['category_id']));
				
				$url = substr($p, strpos($p, '/')+1);*/
				
				$url = str_replace($this->host, '', $p, $num);
				
				if (!$num) $url = substr($url, strpos(str_replace('://', '', $url), '/')+1);
				
				$url = str_replace('index.php?route=product/category&amp;path', '', $url, $no_seo);
			
				$is_seo = $no_seo ? 0 : 1;
				
				$data[$result['category_id']] = array(
					'url' => $url,
					'is_seo' => $is_seo,
					'date_modified' => $date
				);
				
			}
			
		}
		
		$this->cache->set('fx_sitemap4.categories.' . $this->config->get('config_language_id'), $data);

		$this->categories = $data;
		
		$end = (time() + (float)microtime());
		$time = round($end - $start, 5)*1000;		
		
		/*
		$this->response->addHeader('Content-Type: application/html');
		$this->response->setOutput($time . 'ms - ' . count($cat_cache) . ' values');
		
		return $time . 'ms - ' . count($cat_cache) . ' values';*/

	}
	
	protected function exclude($url){

		if (!$this->settings['exclude_file_on']) return false;
		
		foreach ($this->excluded as $ex){
			
			if (substr($ex, -1) == '*') {
				
				$find = str_replace('*', '', $ex);
			
				if (strpos($url, $find) !== false) return true;
				
			}else{
			
				if ($url == $ex) return true;
			
			}

		}

		return false;
		
	}
	
	public function cat_db(){
		
		$data = array();
		
		$this->load->model('extension/module/fx_sitemap');
	
		$results = $this->model_extension_module_fx_sitemap->getCategoriesStore();
		
		$host = $this->config->get('config_secure') ? str_replace('http:/', 'https:/', $this->config->get('config_ssl')) : $this->config->get('config_url');
		
		foreach ($results as $result) {
			
			$date = date_format( new DateTime($result['date_modified']), 'Y-m-d');
			if ($date == '-0001-11-30') $date = date_format( new DateTime($result['date_added']), 'Y-m-d');
			if ($date == '-0001-11-30') $date = date('Y-m-d');			
			
			if((float)VERSION >= 3){
			
				$this->load->model('localisation/language');
				$languages = $this->model_localisation_language->getLanguages();
				
				foreach ($languages as $language) {
				
					
					$this->model_extension_module_fx_sitemap->lang($language['language_id']);
					
					$p = $this->url->link('product/category', ((float)VERSION >= 3.1 ? 'language=' . $language['language_id'] . '&' : '') . 'path=' . $result['category_id']);
					
					
					if (isset($settings['amp'])) $p = str_replace("&path=", "&amp;path=", $p);
					
					$parts = parse_url($p);
					
					$url = ltrim($parts['path'], '/') . (isset($parts['query']) ? '?' . $parts['query'] : '');
					$url = str_replace('index.php?route=product/category&amp;path', '', $url, $no_seo);
					
					$is_seo = $no_seo ? 0 : 1;
					
					$data[] = array(
						'category_id' => $result['category_id'],
						'url' => $url,
						'is_seo' => $is_seo,
						'store_id' => $result['store_id'],
						'date' => $date,
						'language_id' => $language['language_id']
					);
				}
			} else {
				
				$p = $this->url->link('product/category', 'path=' . $result['category_id']);
				
				if (isset($settings['amp'])) $p = str_replace("&path=", "&amp;path=", $p);
				
				$parts = parse_url($p);
				
				$is_seo = 1;

				$url = ltrim($parts['path'], '/') . (isset($parts['query']) ? '?' . $parts['query'] : '');
				$url = str_replace('index.php?route=product/category&amp;path', '', $url, $no_seo);
				
				if ($no_seo) $is_seo = 0;
				
				$data[] = array(
					'category_id' => $result['category_id'],
					'url' => $url,
					'is_seo' => $is_seo,
					'store_id' => $result['store_id'],
					'date' => $date
				);
				
			}
		}
		
		$results = $this->model_extension_module_fx_sitemap->PasteCatDB($data);		
		
		return count($data) . ' categories';
		
		
	}
	
	public function category_path($category_id){
	

		return $path;
		
	}
	
	public function get_page_md5($url){
		
		/*$ch = curl_init();
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; FX)');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL, $url);
		$pagecode = curl_exec( $ch );
		curl_close($ch);*/
		
		ini_set('default_socket_timeout', 10);
		
		$headers = @get_headers($url);
		
		if($headers !== false){
		
			$pagecode = @file_get_contents($url);
			
			return md5(strip_tags($pagecode));
		} 
		
		return false;
    }
	
	public function language_id(){
		
		$language_id = isset($this->settings['language']) ? $this->settings['language'] : (int)$this->config->get('config_language_id');
		
		$this->load->model('extension/module/fx_sitemap');
		
		$this->model_extension_module_fx_sitemap->lang($language_id);
		
		//$language_id = $this->model_extension_module_fx_sitemap->getLanguageId($language);

		return $language_id;		
	}
	
	public function update_dates(){
	
		$dates = array();		
		
		$this->load->model('extension/module/fx_sitemap');
		
		$categories = $this->model_extension_module_fx_sitemap->getCategories();
		
		foreach ($categories as $category) {										
			
			$this->model_extension_module_fx_sitemap->setCategoryDate($category['category_id']);
			
		}
		
		$i = count($categories);

		return count($categories) . ' categories';
		
	}
	
	protected function settings($settings){	

		$keys = array('default', 'key', 'slash', 'log');
		
		foreach ($keys as $key){
			
			if (!isset($settings[$key])) $settings[$key] = false;

		}
		

		return $settings;
		
	}
	
	public function clearlog(){

		file_put_contents(DIR_LOGS.'fx_sitemap.log', '');
		
	}
	
	public function generate_pages_lastmod(){
	
		$this->load->model('extension/module/fx_sitemap');
		
		$update = array();
		
		$message = '';
		
		
		$md5['home'] = $this->get_page_md5($this->url->link('common/home', '', 'SSL'));
		$md5['special'] = $this->get_page_md5($this->url->link('product/special', '', 'SSL'));
		$md5['contact'] = $this->get_page_md5($this->url->link('information/contact', '', 'SSL'));		
		
		$pages = $this->model_extension_module_fx_sitemap->getPages();
		
		if (empty($pages)){
		
			$pages['home'] = array(
				'name' => 'home',
				'md5' => $md5['home'],
				'date' => date('Y-m-d')
			);
			
			$pages['special'] = array(
				'name' => 'special',
				'md5' => $md5['special'],
				'date' => date('Y-m-d')
			);
			
			$pages['contact'] = array(
				'name' => 'contact',
				'md5' => $md5['contact'],
				'date' => date('Y-m-d')
			);
		
		}else{		
		
		
			if ($pages['home']['md5'] != $md5['home']) { 
				$pages['home'] = array(
					'name' => 'home',
					'md5' => $md5['home'], 
					'date' => date('Y-m-d')
				); 
				$message .= 'homepage'; 
			}
			
			if ($pages['special']['md5'] != $md5['special']) { 
				$pages['special'] = array(
					'name' => 'special',
					'md5' => $md5['special'], 
					'date' => date('Y-m-d')
				); 
				$message .= ', specials'; 
			}
			
			if ($pages['contact']['md5'] != $md5['contact']) { 
				$pages['contact'] = array(
					'name' => 'contact',
					'md5' => $md5['contact'], 
					'date' => date('Y-m-d')
				); 
				$message .= ', contact﻿s'; 
			}
			
		}
		
		$this->model_extension_module_fx_sitemap->setPages($pages);
		
		
		return ltrim($message, ',');
		
	}
	
	public function cron(){
	
		$report = '<html><head><title>FX Silemap Cron Log</title></head><body>';
		
		$report = '<div style="align:center; margin: 10px; border: 2px dotted #dff; padding: 10px; border-radius: 6px; background: #adf" class="alert-danger">';
		
		
		$report .= '<b>Update Lastmod on Categories by Products complete... </b>' . $this->update_dates() . '<br>';
		
		$report .= '<b>Generate Manufacturers on Categories complete... </b>' . $this->generateManufacturersOnCategory() . '<br>';
		
		$report .= '<b>Generate Categories Super Cache (on DB) complete... </b>' . $this->cat_db() . '<br>';
		
		$report .= '<b>Generate Homepage & Special lastmod: </b>' . $this->generate_pages_lastmod() . '<br>';
		
		
		$report .= '</div>';
		
		$report .= '</body></html>';
		
		$this->response->addHeader('Content-Type: text/html');
		$this->response->setOutput($report);
		
	}
	
	public function generateManufacturersOnCategory(){

		$this->load->model('extension/module/fx_sitemap');
		
		$data = array();
		
		$i = 0;
		
		$categories = $this->model_extension_module_fx_sitemap->getCategories();
		
		foreach ($categories as $category) {

			$num = 0; 
			$manufacturers_list = '';
			
			$manufacturers = $this->model_extension_module_fx_sitemap->getManufacturersOnCategory($category['category_id']);			
			
			foreach ($manufacturers as $manufacturer) {
			
				if ($manufacturer['manufacturer_id']) {
					$manufacturers_list .= ',' . $manufacturer['manufacturer_id'];
					$i++; $num++;
				}			
			}
			
			if ($manufacturers_list) $data[] = array(
			
				'category_id' => $category['category_id'],
				'manufacturers' => ltrim($manufacturers_list, ','),
				'num' => $num
			);
			
		}
		
		$this->model_extension_module_fx_sitemap->PasteManCatDB($data);
		
		return count($categories) . ' categories and ' . $i . ' manufacturers';
	}
}
