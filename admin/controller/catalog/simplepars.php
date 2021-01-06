<?php
set_time_limit(0);
class ControllerCatalogSimplePars extends Controller
{
    private $error = array();
    public function index()
    {
        $adap = $this->adap();
        $data["adap"] = $adap;
        $this->document->setTitle("Главная страница - SimplePars");
        $this->load->model("catalog/simplepars");
        if (isset($this->session->data["error"])) {
            $data["error"] = $this->session->data["error"];
            unset($this->session->data["error"]);
        } else {
            $data["error"] = "";
        }
        if (isset($this->session->data["success"])) {
            $data["success"] = $this->session->data["success"];
            unset($this->session->data["success"]);
        } else {
            $data["success"] = "";
        }
        if ($this->request->server["REQUEST_METHOD"] == "POST" && isset($this->request->post["dn_del"])) {
            if (!empty($this->request->post["dn_id"])) {
                $this->model_catalog_simplepars->DnDel($this->request->post["dn_id"]);
            } else {
                $data["error"] = "Не выбран проект на удаление";
            }
        }
    
        //Спарсим дополнительные категории товара для сайта eko-bike.ru
        if ($this->request->server["REQUEST_METHOD"] == "GET" && isset($this->request->get["add_cats"])) {
            //Зарузим модель для работы с категориями
            $this->load->model('catalog/category');
            $this->load->language('catalog/category');
            
            //подключим phpQuery
            require_once DIR_SYSTEM . 'library/phpQuery/phpQuery-onefile.php';
            require_once DIR_SYSTEM . 'library/phpQuery/ostap-functions.php';
            //show("add_cats!!!");
            $dn_id = $this->request->get["add_cats"];
            show($dn_id);
    
            //зададим необходимые переменные
            $domain = 'https://eko-bike.ru/';
            $clear_domain = 'https://eko-bike.ru';
            $sleep = 1;
            $sub_categories_a = '.menu_tags .menu_hide a';
            $sub_categories_button = '.menu_tags .menu_hide button';
            $product_a = '#fn_products_content a.product_preview__name_link';
            $product_h4 = '#fn_products_content a.product_preview__name_link h4';
    
            $start_link = $this->model_catalog_simplepars->GetStartLink($dn_id);
            dump($start_link);
            
            if(!empty($start_link) and strstr($start_link, $clear_domain)){
                
                $cat_d = $this->model_catalog_simplepars->GetParentCat($dn_id);
                //dump($cat_d);
    
                //начнем парсинг
                $file = file_get_contents($start_link);
                sleep($sleep);
                $html = phpQuery::newDocument($file);
    
                //find all pagination hrefs
                $SubCategoriesLinks = [];
                $WhatFind = $html->find($sub_categories_a);
                foreach ($WhatFind as $element) {
                    $pq = pq($element); // pq() - Это аналог $ в jQuery
                    $href = $pq->attr('href');
                    if(!empty($href)){
                        $SubCategoriesLinks[] = array(
                            'name' => trim($pq->text()),
                            'href' => $domain.$href
                        );
                    }
                }
                //dump($SubCategoriesLinks);
    
                //find all pagination buttons
                $WhatFind = $html->find($sub_categories_button);
                foreach ($WhatFind as $element) {
                    $pq = pq($element); // pq() - Это аналог $ в jQuery
                    $href = $pq->attr('value');
                    if(!empty($href) and strlen($href)>21){
                        $SubCategoriesLinks[] = array(
                            'name' => trim($pq->text()),
                            'href' => $href
                        );
                    }
                }
                //dump($SubCategoriesLinks);
    
                foreach($SubCategoriesLinks as $SubCategory) {
                    $category_name = $SubCategory['name'];
                    $category_link = $SubCategory['href'];
        
                    $category_id = $this->model_catalog_simplepars->GetCategoryByName($cat_d, $category_name);
                    if($category_id){
            
                        $pars_cat_id = $this->model_catalog_simplepars->GetParsCat($dn_id, $cat_d, $category_id);
                        if(!$pars_cat_id){
                            //если категория есть заполняем таблицу
                            //show("Найдена категория $category_id");
                            $pars_cat_id = $this->model_catalog_simplepars->AddToParsCats($dn_id, $cat_d, $category_id, $category_name, $category_link);
                        }
            
                    }
                    else{
                        //если категории нет сперва добавим ее а потом заполним таблицу
                        $category_data = [];
                        $category_data['parent_id'] = $cat_d;
                        $category_data['top'] = 0;
                        $category_data['column'] = 1;
                        $category_data['sort_order'] = 0;
                        $category_data['status'] = 1;
                        $category_data['noindex'] = 1;
                        $category_data['is_tag'] = 0;
                        $category_data['category_description'] = array(
                            1 => array(
                                'name' => $category_name,
                                'description' => '',
                                'meta_title' => $category_name,
                                'meta_h1' => $category_name,
                                'meta_description' => '',
                                'meta_keyword' => '',
                            )
                        );
                        $category_data['category_seo_url'] = array(
                            0 => array(
                                1 => translit($category_name)
                            )
                        );
                        $category_data['category_store'] = array(0);
                        $category_id = $this->model_catalog_category->addCategory($category_data);
            
                        if($category_id){
                            $pars_cat_id = $this->model_catalog_simplepars->GetParsCat($dn_id, $cat_d, $category_id);
                            if(!$pars_cat_id){
                                // dump($category_id);
                                $pars_cat_id = $this->model_catalog_simplepars->AddToParsCats($dn_id, $cat_d, $category_id, $category_name, $category_link);
                                //show("Добавлена категория $category_id и в таблицу парсинга под номером $pars_cat_id");
                            }
                        }
            
                    }
        
                }
    
                //Работаем с товарами
                $pars_categories = $this->model_catalog_simplepars->GetParsCats($dn_id, $cat_d);
    
                $cnt = 0;
                foreach ($pars_categories as $pars_category){
        
                    if($cnt>2){
                        die();
                    }
        
                    //начнем парсинг
                    $cat_link = $pars_category['cat_link'].'page-all';
                    $file = file_get_contents($cat_link);
                    sleep($sleep);
                    $html = phpQuery::newDocument($file);
        
                    //find all pagination hrefs
                    $ProductsLinks = [];
                    $WhatFind = $html->find($product_a);
                    foreach ($WhatFind as $element) {
                        $pq = pq($element); // pq() - Это аналог $ в jQuery
                        $href = $pq->attr('href');
            
                        $h4 = $pq->find('h4');
                        $pq_h4 = pq($h4);
                        $name = $pq_h4->text();
            
                        if(!empty($href)){
                            $ProductsLinks[] = array(
                                'name' => trim($name),
                                'href' => $clear_domain.$href
                            );
                
                
                            $product_id = $this->model_catalog_simplepars->GetProductId($dn_id, $name, $pars_category['cat_id']);
                            if($product_id){
                                $this->model_catalog_simplepars->UpdateProductCategories($product_id, $pars_category['cat_id']);
                                show_strong("обновлены категории у товара $product_id");
                            }
                
                        }
                    }
                    //dump($ProductsLinks);
        
                    $cnt++;
                }
    
            }
            
        }
        //Спарсим дополнительные категории товара для сайта eko-bike.ru КОНЕЦ
        
        
        $data["dn_add_link"] = $this->url->link("catalog/simplepars/dnadd", $adap["token"], true);
        $data["link_module"] = $this->url->link("catalog/simplepars", $adap["token"], true);
        $data["act_link"] = $this->url->link("catalog/simplepars/act", $adap["token"] . "&do=1", true);
        $data["cron_link"] = $this->url->link("catalog/simplepars/cron", $adap["token"], true);
        $data["cron_status"] = $this->model_catalog_simplepars->getCronMain();
        if ($data["cron_status"]["permit"] == "stop") {
            $data["cron_status"] = "<span class=\"text-default\">Планировщик задач cron выключен</span>";
        } else {
            $data["cron_status"] = "<span class=\"text-warning\">Планировщик задач cron включен</span>";
        }
        $getpageinfo = $this->model_catalog_simplepars->getIndexPage();
        $data["link_dn"] = $this->url->link("catalog/simplepars/grab", $adap["token"] . "&dn_id=", true);
        $data["pars_setting"] = $getpageinfo["pars_settings"];
        $data["cron_permit"] = $getpageinfo["cron_permit"];
        $data["cron_button"] = $getpageinfo["cron_button"];
        $data["cron_text"] = $getpageinfo["cron_text"];
        if (empty($data["pars_setting"])) {
            $data["pars_setting"] = array();
            $data["error"] = "У вас нет созданных проектов.";
        }
        $data["breadcrumbs"] = $this->breadcrumbs($adap);
        $data["header"] = $this->load->controller("common/header");
        $data["column_left"] = $this->load->controller("common/column_left");
        $data["footer"] = $this->load->controller("common/footer");
        if (empty($data["error"])) {
            $data["error"] = "";
        }
        if (!empty($this->request->post["cron_permit"])) {
            $this->model_catalog_simplepars->cronOnOff($this->request->post);
            $this->response->redirect($this->url->link("catalog/simplepars", $adap["token"], true));
        }
        $this->response->setOutput($this->load->view("catalog/simplepars" . $adap["exten"], $data));
    }
    public function dnadd()
    {
        $adap = $this->adap();
        $data["adap"] = $adap;
        $this->document->setTitle("Создание проекта - SimplePars");
        $this->load->model("catalog/simplepars");
        $data["header"] = $this->load->controller("common/header");
        $data["column_left"] = $this->load->controller("common/column_left");
        $data["footer"] = $this->load->controller("common/footer");
        if (isset($this->session->data["error"])) {
            $data["error"] = $this->session->data["error"];
            unset($this->session->data["error"]);
        } else {
            $data["error"] = "";
        }
        if (isset($this->session->data["success"])) {
            $data["success"] = $this->session->data["success"];
            unset($this->session->data["success"]);
        } else {
            $data["success"] = "";
        }
        $data["breadcrumbs"] = $this->breadcrumbs($adap);
        if (!empty($this->request->post["dn_add"])) {
            $this->model_catalog_simplepars->DnAdd($this->request->post["dn_name"]);
            $this->response->redirect($this->url->link("catalog/simplepars", $adap["token"], true));
        }
        $this->response->setOutput($this->load->view("catalog/simplepars_add" . $adap["exten"], $data));
    }
    public function grab()
    {
        $adap = $this->adap();
        $data["adap"] = $adap;
        $data["dn_id"] = (int) $this->request->get["dn_id"];
        $this->document->setTitle("Сбор ссылок - SimplePars");
        $this->load->model("catalog/simplepars");
        $viemgrab = $this->model_catalog_simplepars->ViemGrab($data["dn_id"]);
        $data["header"] = $this->load->controller("common/header");
        $data["column_left"] = $this->load->controller("common/column_left");
        $data["footer"] = $this->load->controller("common/footer");
        $data["submit_pars_link_stop"] = $this->url->link("catalog/simplepars/grab", $adap["token"] . "&act=stop_grab&dn_id=" . $data["dn_id"], true);
        $data["setting"] = $viemgrab["setting"];
        $data["round_link"] = $viemgrab["round_link"];
        $data["round_links_prepare"] = $viemgrab["round_links_prepare"];
        $data["finish_link"] = $viemgrab["finish_link"];
        $data["links_prepare"] = $viemgrab["links_prepare"];
        $data["count_finish_scan"] = $viemgrab["count_finish_scan"];
        $data["browser"] = $viemgrab["browser"];
        $data["breadcrumbs"] = $this->breadcrumbs($adap);
        $data["mpage"] = $this->mPage();
        if (isset($this->session->data["error"])) {
            $data["error"] = $this->session->data["error"];
            unset($this->session->data["error"]);
        } else {
            $data["error"] = "";
        }
        if (isset($this->session->data["success"])) {
            $data["success"] = $this->session->data["success"];
            unset($this->session->data["success"]);
        } else {
            $data["success"] = "";
        }
        if ($this->request->server["REQUEST_METHOD"] == "POST" && $this->validateForm()) {
            if (isset($this->request->post["start_grab"])) {
                $this->model_catalog_simplepars->grabControl((int) $this->request->post["start_grab"], $data["dn_id"]);
            }
            if (isset($this->request->post["save_grab"])) {
                $this->model_catalog_simplepars->SeveFormGrab($this->request->post, $data["dn_id"]);
                $this->response->redirect($this->url->link("catalog/simplepars/grab", $adap["token"] . "&dn_id=" . $data["dn_id"], true));
            }
            if (isset($this->request->post["update_grab"])) {
                $this->response->redirect($this->url->link("catalog/simplepars/grab", $adap["token"] . "&dn_id=" . $data["dn_id"], true));
            }
            if (isset($this->request->post["del_link_round"])) {
                $this->model_catalog_simplepars->DelParsSenLink($data["dn_id"]);
                $this->response->redirect($this->url->link("catalog/simplepars/grab", $adap["token"] . "&dn_id=" . $data["dn_id"], true));
            }
            if (isset($this->request->post["del_finish_link"])) {
                $this->model_catalog_simplepars->DelParsLink($data["dn_id"]);
                $this->response->redirect($this->url->link("catalog/simplepars/grab", $adap["token"] . "&dn_id=" . $data["dn_id"], true));
            }
            if (isset($this->request->post["use_filter_round"])) {
                $this->model_catalog_simplepars->UseNewFilter($data["dn_id"], "filter_round");
                $this->response->redirect($this->url->link("catalog/simplepars/grab", $adap["token"] . "&dn_id=" . $data["dn_id"], true));
            }
            if (isset($this->request->post["use_filter_finish"])) {
                $this->model_catalog_simplepars->UseNewFilter($data["dn_id"], "filter_link");
                $this->response->redirect($this->url->link("catalog/simplepars/grab", $adap["token"] . "&dn_id=" . $data["dn_id"], true));
            }
            if (isset($this->request->post["links_sen_restart"])) {
                $this->model_catalog_simplepars->linksSenRestart($data["dn_id"]);
                $this->response->redirect($this->url->link("catalog/simplepars/grab", $adap["token"] . "&dn_id=" . $data["dn_id"], true));
            }
            if (isset($this->request->post["seve_links_sen"])) {
                $this->model_catalog_simplepars->controlAddLink($this->request->post["link_round"], $data["dn_id"], $mark = "link_sen");
                $this->response->redirect($this->url->link("catalog/simplepars/grab", $adap["token"] . "&dn_id=" . $data["dn_id"], true));
            }
            if (isset($this->request->post["seve_links"])) {
                $this->model_catalog_simplepars->controlAddLink($this->request->post["links"], $data["dn_id"], $mark = "link");
                $this->response->redirect($this->url->link("catalog/simplepars/grab", $adap["token"] . "&dn_id=" . $data["dn_id"], true));
            }
            if (isset($this->request->post["file"]) && $this->request->post["file"] == "file_links") {
                if (is_uploaded_file($this->request->files["import"]["tmp_name"])) {
                    $form = file_get_contents($this->request->files["import"]["tmp_name"]);
                    if ($form) {
                        $this->model_catalog_simplepars->uploadLinkFromFile($form, $data["dn_id"], $this->request->post["file_link_who"]);
                        $this->response->redirect($this->url->link("catalog/simplepars/grab", $adap["token"] . "&dn_id=" . $data["dn_id"], true));
                    } else {
                        $data["error"] = " Фаил ссылок пустой.";
                    }
                } else {
                    $data["error"] = " Не выбран файл настроек для загрузки";
                }
            }
        }
        $this->response->setOutput($this->load->view("catalog/simplepars_grab" . $adap["exten"], $data));
    }
    public function paramsetup()
    {
        $adap = $this->adap();
        $data["adap"] = $adap;
        $data["dn_id"] = (int) $this->request->get["dn_id"];
        $this->document->setTitle("Настройки парсинга - SimplePars");
        $this->load->model("catalog/simplepars");
        $data["header"] = $this->load->controller("common/header");
        $data["column_left"] = $this->load->controller("common/column_left");
        $data["footer"] = $this->load->controller("common/footer");
        $data["breadcrumbs"] = $this->breadcrumbs($adap);
        if (isset($this->session->data["error"])) {
            $data["error"] = $this->session->data["error"];
            unset($this->session->data["error"]);
        } else {
            $data["error"] = "";
        }
        if (isset($this->session->data["success"])) {
            $data["success"] = $this->session->data["success"];
            unset($this->session->data["success"]);
        } else {
            $data["success"] = "";
        }
        $data["mpage"] = $this->mPage();
        $getparamsetup = $this->model_catalog_simplepars->GetParamsetup($data["dn_id"]);
        $data["hrefs"] = $getparamsetup["hrefs"];
        $data["params"] = $getparamsetup["params"];
        $data["setting"] = $getparamsetup["setting"];
        $data["browser"] = $getparamsetup["browser"];
        $data["view_href"] = $this->url->link("catalog/simplepars/paramsetup", $adap["token"] . "&dn_id=" . $data["dn_id"] . "&url_id=", true);
        $data["page_code"] = "<h1><strong>Warning!</strong> Не выбрана ССЫЛКА для просмотра кода ---></h1>";
        $data["view_link"] = "";
        $data["menup"]["type_param"] = 1;
        if (!empty($this->request->post["view_href"])) {
            $data["view_link"] = str_replace("&amp;", "&", $this->request->post["view_href"]);
            $show_url = $this->model_catalog_simplepars->getUrlId($data["view_link"]);
            if (empty($show_url["id"])) {
                $show_url["id"] = 0;
                $this->session->data["view_link"] = $data["view_link"];
            }
            $this->response->redirect($this->url->link("catalog/simplepars/paramsetup", $adap["token"] . "&dn_id=" . $data["dn_id"] . "&url_id=" . (int) $show_url["id"], true));
        }
        if (isset($this->request->get["url_id"])) {
            $url_id = (int) $this->request->get["url_id"];
            if ($url_id == 0) {
                $data["view_link"] = $this->session->data["view_link"];
                $data["page_code"] = @htmlspecialchars(@$this->model_catalog_simplepars->CachePage($this->session->data["view_link"], $data["dn_id"]));
                unset($this->session->data["view_link"]);
            } else {
                $show_url = $this->model_catalog_simplepars->getUrlFromId($this->request->get["url_id"]);
                if (!empty($show_url["link"])) {
                    $data["view_link"] = $show_url["link"];
                    $data["page_code"] = htmlspecialchars($this->model_catalog_simplepars->CachePage($show_url["link"], $data["dn_id"]));
                }
            }
        }
        if (isset($this->request->post["del_param"]) && $this->request->post["del_param"] == "yes") {
            if ($this->request->post["get_param_id"] == 0) {
                $data["error"] = "Для удаления необходимо выбрать границу парсинга";
            } else {
                $this->model_catalog_simplepars->delParamPars($this->request->post["get_param_id"]);
                $this->response->redirect($this->url->link("catalog/simplepars/paramsetup", $adap["token"] . "&dn_id=" . $data["dn_id"], true));
            }
        }
        if (isset($this->request->post["pre_view_param"])) {
            $this->model_catalog_simplepars->setViewParam($this->request->post, $data["dn_id"]);
            $this->response->redirect($this->url->link("catalog/simplepars/paramsetup", $adap["token"] . "&dn_id=" . $data["dn_id"], true));
        }
        if (isset($this->request->post["cache_page"])) {
            $this->model_catalog_simplepars->changeCacheParam($this->request->post, $data["dn_id"]);
            $this->response->redirect($this->url->link("catalog/simplepars/paramsetup", $adap["token"] . "&dn_id=" . $data["dn_id"], true));
        }
        $this->response->setOutput($this->load->view("catalog/simplepars_paramsetup" . $adap["exten"], $data));
    }
    public function createcsv()
    {
        $adap = $this->adap();
        $data["adap"] = $adap;
        $data["dn_id"] = (int) $this->request->get["dn_id"];
        $this->document->setTitle("CSV/Парсинг - SimplePars");
        $this->load->model("catalog/simplepars");
        $data["header"] = $this->load->controller("common/header");
        $data["column_left"] = $this->load->controller("common/column_left");
        $data["footer"] = $this->load->controller("common/footer");
        $data["breadcrumbs"] = $this->breadcrumbs($adap);
        if (isset($this->session->data["success"])) {
            $data["success"] = $this->session->data["success"];
            unset($this->session->data["success"]);
        } else {
            $data["success"] = "";
        }
        if (isset($this->session->data["error"])) {
            $data["error"] = $this->session->data["error"];
            unset($this->session->data["error"]);
        } else {
            $data["error"] = "";
        }
        $data["mpage"] = $this->mPage();
        $createcsv = $this->model_catalog_simplepars->GetParamsetup($data["dn_id"]);
        $getformcsv = $this->model_catalog_simplepars->getFormCsv($data["dn_id"]);
        $data["params"] = $createcsv["params"];
        $data["links_select"] = $getformcsv["links_select"];
        $data["formcsv"] = $getformcsv["formcsv"];
        $data["setting"] = $getformcsv["setting"];
        $data["browser"] = $getformcsv["browser"];
        $data["csv_exists"] = $getformcsv["csv_exists"];
        $data["setup"] = $getformcsv["setup"];
        $data["link_lists"] = $getformcsv["link_lists"];
        $data["link_errors"] = $getformcsv["link_errors"];
        $data["view_href"] = "";
        if (!is_array($data["formcsv"]) || empty($data["formcsv"])) {
            $data["key_finish"] = NULL;
        } else {
            $data["key_finish"] = array_keys($data["formcsv"])[count($data["formcsv"]) - 1];
        }
        if (!is_array($data["setup"]["grans_permit_list"]) || empty($data["setup"]["grans_permit_list"])) {
            $data["grans_permit_key_max"] = 1;
        } else {
            $data["grans_permit_key_max"] = array_keys($data["setup"]["grans_permit_list"])[count($data["setup"]["grans_permit_list"]) - 1];
        }
        if (isset($this->request->post["save_form_csv"])) {
            $this->model_catalog_simplepars->saveFormCsv($this->request->post, $data["dn_id"]);
            $this->response->redirect($this->url->link("catalog/simplepars/createcsv", $adap["token"] . "&dn_id=" . $data["dn_id"], true));
        }
        if (isset($this->request->post["pars_data_start"])) {
            $this->model_catalog_simplepars->controlParsDataToCsv($data["dn_id"]);
        }
        if (!empty($this->request->post["download_csv"])) {
            $this->model_catalog_simplepars->dwFile("csv", $data["dn_id"]);
        }
        if (!empty($this->request->post["del_csv"])) {
            $this->model_catalog_simplepars->delFile($data["dn_id"]);
            $this->response->redirect($this->url->link("catalog/simplepars/createcsv", $adap["token"] . "&dn_id=" . $data["dn_id"], true));
        }
        if (isset($this->request->post["go_show"])) {
            if (empty($this->request->post["view_href"])) {
                if (empty($this->request->post["view_href2"])) {
                    $this->session->data["error"] = "Выберите ссылку для пред просмотра";
                    $this->response->redirect($this->url->link("catalog/simplepars/createcsv", $adap["token"] . "&dn_id=" . $data["dn_id"], true));
                } else {
                    $this->request->post["view_href"] = $this->request->post["view_href2"];
                }
            }
            $data["view_href"] = $this->request->post["view_href"];
            $answer = $this->model_catalog_simplepars->controlShowParsToCsv($data["view_href"], $data["dn_id"]);
            if ($answer == "redirect") {
                $this->response->redirect($this->url->link("catalog/simplepars/createcsv", $adap["token"] . "&dn_id=" . $data["dn_id"], true));
            }
            $data["show"] = $answer;
        } else {
            if (!empty($this->request->get["url_id"])) {
                $show_url = $this->model_catalog_simplepars->getUrlFromId($this->request->get["url_id"]);
                if (!empty($show_url["link"])) {
                    $data["view_href"] = $show_url["link"];
                    $answer = $this->model_catalog_simplepars->controlShowParsToCsv($data["view_href"], $data["dn_id"]);
                    if ($answer == "redirect") {
                        $this->response->redirect($this->url->link("catalog/simplepars/createcsv", $adap["token"] . "&dn_id=" . $data["dn_id"], true));
                    }
                    $data["show"] = $answer;
                }
            }
        }
        $this->response->setOutput($this->load->view("catalog/simplepars_createcsv" . $adap["exten"], $data));
    }
    public function replace()
    {
        $adap = $this->adap();
        $data["adap"] = $adap;
        $data["dn_id"] = (int) $this->request->get["dn_id"];
        $this->document->setTitle("Поиск/Замена - SimplePars");
        $this->load->model("catalog/simplepars");
        if (!empty($this->request->get["param_id"])) {
            $param_id = (int) $this->request->get["param_id"];
        } else {
            $param_id = "";
        }
        $data["header"] = $this->load->controller("common/header");
        $data["column_left"] = $this->load->controller("common/column_left");
        $data["footer"] = $this->load->controller("common/footer");
        $data["breadcrumbs"] = $this->breadcrumbs($adap);
        if (isset($this->session->data["success"])) {
            $data["success"] = $this->session->data["success"];
            unset($this->session->data["success"]);
        } else {
            $data["success"] = "";
        }
        if (isset($this->session->data["error"])) {
            $data["error"] = $this->session->data["error"];
            unset($this->session->data["error"]);
        }
        $data["mpage"] = $this->mPage();
        $data["get_param_href"] = $this->url->link("catalog/simplepars/replace", $adap["token"] . "&dn_id=" . $data["dn_id"] . "&param_id=", true);
        $replace = $this->model_catalog_simplepars->getReplacePage($data["dn_id"], $param_id);
        $data["params"] = $replace["params"];
        $data["replace"] = $replace["replace"];
        $data["replace_links"] = $replace["replace_links"];
        $data["setting"] = $this->model_catalog_simplepars->getSetting($data["dn_id"]);
        if (!empty($this->session->data["rep_view_href"])) {
            $data["view_href"] = $this->session->data["rep_view_href"];
            unset($this->session->data["rep_view_href"]);
        } else {
            $data["view_href"] = "";
        }
        if (!empty($replace["show"])) {
            $data["show"] = $replace["show"];
        }
        if (empty($this->request->get["param_id"])) {
            $data["param_id"] = 0;
            $data["param_name"] = "";
        } else {
            $data["param_id"] = (int) $this->request->get["param_id"];
            $data["param_name"] = "";
            foreach ($data["params"] as $param) {
                if ($data["param_id"] == $param["id"]) {
                    if ($param["type"] == 2) {
                        $data["param_name"] = "@ " . $param["name"];
                        $data["param_type"] = 2;
                    } else {
                        $data["param_name"] = $param["name"];
                        $data["param_type"] = 1;
                    }
                }
            }
        }
        if (isset($this->request->post["save_replace"])) {
            $this->model_catalog_simplepars->saveReplacePage($this->request->post, $data["dn_id"], $param_id);
            $this->response->redirect($this->url->link("catalog/simplepars/replace", $adap["token"] . "&dn_id=" . $data["dn_id"] . "&param_id=" . $param_id, true));
        }
        if (isset($this->request->post["check_text"])) {
            $this->model_catalog_simplepars->saveReplacePage($this->request->post, $data["dn_id"], $param_id);
            $this->model_catalog_simplepars->showReplaceText($this->request->post, $param_id);
            $this->response->redirect($this->url->link("catalog/simplepars/replace", $adap["token"] . "&dn_id=" . $data["dn_id"] . "&param_id=" . $param_id, true));
        }
        if (isset($this->request->post["download_param"])) {
            if (empty($this->request->post["download_link"])) {
                $this->request->post["download_link"] = $this->request->post["view_href"];
            }
            $this->session->data["rep_view_href"] = $this->request->post["download_link"];
            $code_param = $this->model_catalog_simplepars->getParamShow($this->request->post, $param_id, $data["dn_id"]);
            $this->response->redirect($this->url->link("catalog/simplepars/replace", $adap["token"] . "&dn_id=" . $data["dn_id"] . "&param_id=" . $param_id, true));
        }
        $this->response->setOutput($this->load->view("catalog/simplepars_replace" . $adap["exten"], $data));
    }
    public function productsetup()
    {
        $adap = $this->adap();
        $data["adap"] = $adap;
        $data["dn_id"] = (int) $this->request->get["dn_id"];
        $this->document->setTitle("Парсинг в ИМ - SimplePars");
        $this->load->model("catalog/simplepars");
        if (!empty($this->request->get["param_id"])) {
            $param_id = (int) $this->request->get["param_id"];
        } else {
            $param_id = "";
        }
        $data["header"] = $this->load->controller("common/header");
        $data["column_left"] = $this->load->controller("common/column_left");
        $data["footer"] = $this->load->controller("common/footer");
        $data["breadcrumbs"] = $this->breadcrumbs($adap);
        if (isset($this->session->data["success"])) {
            $data["success"] = $this->session->data["success"];
            unset($this->session->data["success"]);
        } else {
            $data["success"] = "";
        }
        if (isset($this->session->data["error"])) {
            $data["error"] = $this->session->data["error"];
            unset($this->session->data["error"]);
        }
        $data["mpage"] = $this->mPage();
        $data["params"] = $this->model_catalog_simplepars->getParsParam($data["dn_id"]);
        $data["setup"] = $this->model_catalog_simplepars->getPrsetupToPage($data["dn_id"]);
        $data["setting"] = $this->model_catalog_simplepars->getSettingToProduct($data["dn_id"]);
        $data["manufs"] = $this->model_catalog_simplepars->getManufs();
        $data["categorys"] = $this->model_catalog_simplepars->madeCatTree(1);
        $data["attr_groups"] = $this->model_catalog_simplepars->getAttrGroup();
        $data["stores"] = $this->model_catalog_simplepars->getAllStore();
        $data["langs"] = $this->model_catalog_simplepars->getAllLang();
        $data["stock_status"] = $this->model_catalog_simplepars->getAllStockStatus();
        $data["options"] = $this->model_catalog_simplepars->getAllOpts();
        $data["length_classes"] = $this->model_catalog_simplepars->getLengthClassId();
        $data["weight_classes"] = $this->model_catalog_simplepars->getWeightClassId();
        $data["browser"] = $this->model_catalog_simplepars->getSettingBrowser($data["dn_id"]);
        $data["link_lists"] = $this->model_catalog_simplepars->getAllLinkList($data["dn_id"]);
        $data["link_errors"] = $this->model_catalog_simplepars->getAllLinkError($data["dn_id"]);
        $data["layouts"] = $this->model_catalog_simplepars->getAllLayouts();
        $data["cast_groups"] = $this->model_catalog_simplepars->getAllGroupCustomer();
        if (!is_array($data["setup"]["opts"]) || empty($data["setup"]["opts"])) {
            $data["opt_key_max"] = NULL;
        } else {
            $data["opt_key_max"] = array_keys($data["setup"]["opts"])[count($data["setup"]["opts"]) - 1];
        }
        if (empty($data["setting"]["r_price_spec_date_start"])) {
            $data["setting"]["r_price_spec_date_start"] = date("Y-m-d");
        }
        if (empty($data["setting"]["r_price_spec_date_end"])) {
            $data["setting"]["r_price_spec_date_end"] = "0000-00-00";
        }
        foreach ($data["stores"] as $key_s => $store) {
            if (in_array($store["store_id"], $data["setting"]["r_store"])) {
                $data["stores"][$key_s]["checked"] = 1;
            } else {
                $data["stores"][$key_s]["checked"] = 0;
            }
        }
        foreach ($data["langs"] as $key_l => $lang) {
            if (in_array($lang["language_id"], $data["setting"]["r_lang"])) {
                $data["langs"][$key_l]["checked"] = 1;
            } else {
                $data["langs"][$key_l]["checked"] = 0;
            }
        }
        if (!is_array($data["setup"]["grans_permit_list"]) || empty($data["setup"]["grans_permit_list"])) {
            $data["grans_permit_key_max"] = 1;
        } else {
            $data["grans_permit_key_max"] = array_keys($data["setup"]["grans_permit_list"])[count($data["setup"]["grans_permit_list"]) - 1];
        }
        $data["href_show"] = $this->url->link("catalog/simplepars/productshow", $adap["token"] . "&dn_id=" . $data["dn_id"], true);
        if ($data["setting"]["sid"] == "sku" && $data["setting"]["r_sku"] == 1) {
            $data["error"] = "Нельзя обновлять значение которое является идентификатором товара. Измените действие в поле Артикул (sku)";
        }
        if ($data["setting"]["sid"] == "name" && $data["setting"]["r_name"] == 1) {
            $data["error"] = "Нельзя обновлять значение которое является идентификатором товара. Измените действие в поле Название";
        }
        if (isset($this->request->post["save"])) {
            $this->model_catalog_simplepars->savePrsetup($this->request->post, $data["dn_id"]);
            $this->response->redirect($this->url->link("catalog/simplepars/productsetup", $adap["token"] . "&dn_id=" . $data["dn_id"], true));
        }
        if (isset($this->request->post["pars_data_start"])) {
            $this->model_catalog_simplepars->startParsToIm($data["dn_id"]);
        }
        if (isset($this->request->post["links_restart"])) {
            $this->model_catalog_simplepars->linksRestart($data["dn_id"]);
            $this->response->redirect($this->url->link("catalog/simplepars/productsetup", $adap["token"] . "&dn_id=" . $data["dn_id"], true));
        }
        $this->response->setOutput($this->load->view("catalog/simplepars_productsetup" . $adap["exten"], $data));
    }
    public function productshow()
    {
        $adap = $this->adap();
        $data["adap"] = $adap;
        $data["dn_id"] = (int) $this->request->get["dn_id"];
        $this->document->setTitle("Пред просмотр парсинга ИМ - SimplePars");
        $this->load->model("catalog/simplepars");
        if (!empty($this->request->get["param_id"])) {
            $param_id = (int) $this->request->get["param_id"];
        } else {
            $param_id = "";
        }
        $data["header"] = $this->load->controller("common/header");
        $data["column_left"] = $this->load->controller("common/column_left");
        $data["footer"] = $this->load->controller("common/footer");
        $data["view_href"] = "";
        $data["breadcrumbs"] = $this->breadcrumbs($adap);
        if (isset($this->session->data["success"])) {
            $data["success"] = $this->session->data["success"];
            unset($this->session->data["success"]);
        } else {
            $data["success"] = "";
        }
        if (isset($this->session->data["error"])) {
            $data["error"] = $this->session->data["error"];
            unset($this->session->data["error"]);
        }
        $data["mpage"] = $this->mPage();
        $data["links"] = $this->model_catalog_simplepars->getFormShowProduct($data["dn_id"]);
        $data["back_url"] = $this->url->link("catalog/simplepars/productsetup", $adap["token"] . "&dn_id=" . $data["dn_id"], true);
        $data["http_catalog"] = HTTP_CATALOG;
        $data["setting"] = $this->model_catalog_simplepars->getSetting($data["dn_id"]);
        if (isset($this->request->post["go_show"])) {
            if (empty($this->request->post["view_href"])) {
                if (empty($this->request->post["view_href2"])) {
                    $data["error"] = "Не выбрана ссылка для пред просмотра.";
                } else {
                    $this->request->post["view_href"] = $this->request->post["view_href2"];
                    $data["view_href"] = $this->request->post["view_href"];
                    $data["product"] = $this->model_catalog_simplepars->goShowToIm($this->request->post["view_href"], $data["dn_id"]);
                }
            } else {
                $data["view_href"] = $this->request->post["view_href"];
                $data["product"] = $this->model_catalog_simplepars->goShowToIm($this->request->post["view_href"], $data["dn_id"]);
            }
        } else {
            if (!empty($this->request->get["url_id"])) {
                $show_url = $this->model_catalog_simplepars->getUrlFromId($this->request->get["url_id"]);
                if (!empty($show_url["link"])) {
                    $data["product"] = $this->model_catalog_simplepars->goShowToIm($show_url["link"], $data["dn_id"]);
                }
            }
        }
        $this->response->setOutput($this->load->view("catalog/simplepars_productshow" . $adap["exten"], $data));
    }
    public function listurl()
    {
        $adap = $this->adap();
        $data["adap"] = $adap;
        $data["dn_id"] = (int) $this->request->get["dn_id"];
        $this->document->setTitle("Менеджер ссылок - SimplePars");
        $this->load->model("catalog/simplepars");
        $data["header"] = $this->load->controller("common/header");
        $data["column_left"] = $this->load->controller("common/column_left");
        $data["footer"] = $this->load->controller("common/footer");
        $data["breadcrumbs"] = $this->breadcrumbs($adap);
        if (isset($this->session->data["success"])) {
            $data["success"] = $this->session->data["success"];
            unset($this->session->data["success"]);
        } else {
            $data["success"] = "";
        }
        if (isset($this->session->data["error"])) {
            $data["error"] = $this->session->data["error"];
            unset($this->session->data["error"]);
        }
        $data["mpage"] = $this->mPage();
        $data["setting"] = $this->model_catalog_simplepars->getSetting($data["dn_id"]);
        $data["url_param"] = $this->url->link("catalog/simplepars/paramsetup", $adap["token"] . "&dn_id=" . $data["dn_id"] . "&url_id=", true);
        $data["url_csv"] = $this->url->link("catalog/simplepars/createcsv", $adap["token"] . "&dn_id=" . $data["dn_id"] . "&url_id=", true);
        $data["url_im"] = $this->url->link("catalog/simplepars/productshow", $adap["token"] . "&dn_id=" . $data["dn_id"] . "&url_id=", true);
        $data["url_page"] = $this->url->link("catalog/simplepars/cachedn", $adap["token"] . "&dn_id=" . $data["dn_id"] . "&page=", true);
        $data["list_names"] = $this->model_catalog_simplepars->getAllLinkList($data["dn_id"]);
        $data["list_errors"] = $this->model_catalog_simplepars->getAllLinkError($data["dn_id"]);
        if (!empty($this->request->post["list_add"])) {
            $this->model_catalog_simplepars->addNewLinkList($this->request->post, $data["dn_id"]);
            $this->response->redirect($this->url->link("catalog/simplepars/listurl", $adap["token"] . "&dn_id=" . $data["dn_id"], true));
        }
        if (!empty($this->request->post["list_del"])) {
            $this->model_catalog_simplepars->delLinkList($this->request->post, $data["dn_id"]);
            $this->response->redirect($this->url->link("catalog/simplepars/listurl", $adap["token"] . "&dn_id=" . $data["dn_id"], true));
        }
        $this->response->setOutput($this->load->view("catalog/simplepars_listurl" . $adap["exten"], $data));
    }
    public function tools()
    {
        $adap = $this->adap();
        $data["adap"] = $adap;
        $data["dn_id"] = (int) $this->request->get["dn_id"];
        $this->document->setTitle("Редактор товаров - SimplePars");
        $this->load->model("catalog/simplepars");
        $data["header"] = $this->load->controller("common/header");
        $data["column_left"] = $this->load->controller("common/column_left");
        $data["footer"] = $this->load->controller("common/footer");
        $data["breadcrumbs"] = $this->breadcrumbs($adap);
        if (isset($this->session->data["success"])) {
            $data["success"] = $this->session->data["success"];
            unset($this->session->data["success"]);
        } else {
            $data["success"] = "";
        }
        if (isset($this->session->data["error"])) {
            $data["error"] = $this->session->data["error"];
            unset($this->session->data["error"]);
        }
        $data["mpage"] = $this->mPage();
        if (empty($this->request->get["pt_id"])) {
            $this->request->get["pt_id"] = 0;
        }
        $data["pattern"] = $this->model_catalog_simplepars->toolGetPatternToPage($this->request->get["pt_id"]);
        $data["pattern2"] = $this->model_catalog_simplepars->toolGetPattern($this->request->get["pt_id"]);
        $data["patterns_all"] = $this->model_catalog_simplepars->toolGetAllPatterns($data["dn_id"]);
        $data["setting"] = $this->model_catalog_simplepars->getSetting($data["dn_id"]);
        if ($data["setting"]["vers_op"] == "ocstore2" || $data["setting"]["vers_op"] == "ocstore3") {
            $data["setting"]["vers_op"] = 1;
        } else {
            $data["setting"]["vers_op"] = 0;
        }
        $data["dns_id"] = $this->model_catalog_simplepars->getAllProject();
        $data["categorys"] = $this->model_catalog_simplepars->toolMadeCategoryToPage();
        $data["manufs"] = $this->model_catalog_simplepars->getManufs();
        $data["langs"] = $this->model_catalog_simplepars->getAllLang();
        $data["stock_status"] = $this->model_catalog_simplepars->getAllStockStatus();
        $data["pt_naw"] = $this->request->get["pt_id"];
        $data["url_pt"] = $this->url->link("catalog/simplepars/tools", $adap["token"] . "&dn_id=" . $data["dn_id"] . "&pt_id=", true);
        if (!empty($data["pattern"]["langs"])) {
            foreach ($data["langs"] as $key_l => $lang) {
                if (in_array($lang["language_id"], $data["pattern"]["langs"])) {
                    $data["langs"][$key_l]["checked"] = 1;
                } else {
                    $data["langs"][$key_l]["checked"] = 0;
                }
            }
        } else {
            foreach ($data["langs"] as $key_l => $lang) {
                if ($lang["language_id"] == $this->model_catalog_simplepars->getLangDef()) {
                    $data["langs"][$key_l]["checked"] = 1;
                } else {
                    $data["langs"][$key_l]["checked"] = 0;
                }
            }
        }
        $what = array("\\", "'", PHP_EOL);
        $than = array("\\\\", "\\'", "");
        $data["categorys_do"] = $data["categorys"];
        $data["manufs_do"] = $data["manufs"];
        foreach ($data["manufs_do"] as &$mf) {
            $mf["name"] = str_ireplace($what, $than, $mf["name"]);
        }
        if (!empty($data["pattern"]["cats"])) {
            foreach ($data["categorys"] as $key_c => $cat) {
                if (in_array($cat["id"], $data["pattern"]["cats"])) {
                    $data["categorys"][$key_c]["checked"] = 1;
                } else {
                    $data["categorys"][$key_c]["checked"] = 0;
                }
                $data["categorys"][$key_c]["name"] = str_ireplace($what, $than, $cat["name"]);
            }
        } else {
            foreach ($data["categorys"] as $key_c => $cat) {
                $data["categorys"][$key_c]["checked"] = 0;
                $data["categorys"][$key_c]["name"] = str_ireplace($what, $than, $cat["name"]);
            }
        }
        if (!empty($data["pattern"]["new_cats"])) {
            foreach ($data["categorys_do"] as $key_d => $cat) {
                if (in_array($cat["id"], $data["pattern"]["new_cats"])) {
                    $data["categorys_do"][$key_d]["checked"] = 1;
                } else {
                    $data["categorys_do"][$key_d]["checked"] = 0;
                }
            }
        } else {
            foreach ($data["categorys_do"] as $key_d => $cat) {
                $data["categorys_do"][$key_d]["checked"] = 0;
            }
        }
        if (!empty($data["pattern"]["manufs"])) {
            foreach ($data["manufs"] as $key_m => $manufs) {
                if (in_array($manufs["id"], $data["pattern"]["manufs"])) {
                    $data["manufs"][$key_m]["checked"] = 1;
                } else {
                    $data["manufs"][$key_m]["checked"] = 0;
                }
            }
        } else {
            foreach ($data["manufs"] as $key_m => $manufs) {
                $data["manufs"][$key_m]["checked"] = 0;
            }
        }
        if (isset($this->request->post["get_filter"])) {
            $this->model_catalog_simplepars->toolFilterToPage($this->request->post, $data["dn_id"]);
        }
        if (isset($this->request->post["apply_action"])) {
            $this->model_catalog_simplepars->toolControlerFunction($this->request->post, $data["dn_id"], "user");
        }
        if (isset($this->request->post["pattern_add"])) {
            $this->model_catalog_simplepars->toolAddPattern($this->request->post, $data["dn_id"]);
            $this->response->redirect($this->url->link("catalog/simplepars/tools", $adap["token"] . "&dn_id=" . $data["dn_id"], true));
        }
        if (isset($this->request->post["pattern_update"]) && !empty($this->request->post["pattern_take"])) {
            $this->model_catalog_simplepars->toolUpdatePattern($this->request->post, $data["dn_id"]);
            $this->response->redirect($this->url->link("catalog/simplepars/tools", $adap["token"] . "&dn_id=" . $data["dn_id"] . "&pt_id=" . (int) $this->request->post["pattern_take"], true));
        }
        if (isset($this->request->post["patern_del"])) {
            $this->model_catalog_simplepars->toolDelPattern($this->request->post["pattern_take"]);
            $this->response->redirect($this->url->link("catalog/simplepars/tools", $adap["token"] . "&dn_id=" . $data["dn_id"], true));
        }
        $this->response->setOutput($this->load->view("catalog/simplepars_tools" . $adap["exten"], $data));
    }
    public function logs()
    {
        $adap = $this->adap();
        $data["adap"] = $adap;
        $data["dn_id"] = (int) $this->request->get["dn_id"];
        $this->document->setTitle("Логи - SimplePars");
        $this->load->model("catalog/simplepars");
        $data["header"] = $this->load->controller("common/header");
        $data["column_left"] = $this->load->controller("common/column_left");
        $data["footer"] = $this->load->controller("common/footer");
        $data["breadcrumbs"] = $this->breadcrumbs($adap);
        if (isset($this->session->data["success"])) {
            $data["success"] = $this->session->data["success"];
            unset($this->session->data["success"]);
        } else {
            $data["success"] = "";
        }
        if (isset($this->session->data["error"])) {
            $data["error"] = $this->session->data["error"];
            unset($this->session->data["error"]);
        }
        $data["mpage"] = $this->mPage();
        $data["setting"] = $this->model_catalog_simplepars->getSetting($data["dn_id"]);
        $data["logs"] = $this->model_catalog_simplepars->getLogs($data["dn_id"]);
        if (isset($this->request->post["save_logs_setting"])) {
            $this->model_catalog_simplepars->saveLogSetting($this->request->post, (int) $data["dn_id"]);
            $this->response->redirect($this->url->link("catalog/simplepars/logs", $adap["token"] . "&dn_id=" . $data["dn_id"], true));
        }
        if (isset($this->request->post["dl_lods"])) {
            $file = DIR_LOGS . "simplepars_id-" . (int) $data["dn_id"] . ".log";
            $handle = fopen($file, "w+");
            fclose($handle);
            $this->response->redirect($this->url->link("catalog/simplepars/logs", $adap["token"] . "&dn_id=" . $data["dn_id"], true));
        }
        if (isset($this->request->post["dw_lods"])) {
            $this->model_catalog_simplepars->dwFile("logs", $data["dn_id"]);
        }
        $this->response->setOutput($this->load->view("catalog/simplepars_logs" . $adap["exten"], $data));
    }
    public function splitxml()
    {
        $adap = $this->adap();
        $data["adap"] = $adap;
        $data["dn_id"] = (int) $this->request->get["dn_id"];
        $this->document->setTitle("Обработчик XML - SimplePars");
        $this->load->model("catalog/simplepars");
        $data["header"] = $this->load->controller("common/header");
        $data["column_left"] = $this->load->controller("common/column_left");
        $data["footer"] = $this->load->controller("common/footer");
        $data["breadcrumbs"] = $this->breadcrumbs($adap);
        if (isset($this->session->data["success"])) {
            $data["success"] = $this->session->data["success"];
            unset($this->session->data["success"]);
        } else {
            $data["success"] = "";
        }
        if (isset($this->session->data["error"])) {
            $data["error"] = $this->session->data["error"];
            unset($this->session->data["error"]);
        }
        $data["mpage"] = $this->mPage();
        $get_page = $this->model_catalog_simplepars->getSplitXmpPage($data["dn_id"]);
        $data["setting"] = $get_page["setting"];
        $data["xml"] = $get_page["xml"];
        $data["links"] = $get_page["links"];
        $data["view_href"] = "";
        $data["page_code"] = "<h1><strong>Warning!</strong> Не выбрана ССЫЛКА для просмотра кода ^</h1>";
        $data["browser"] = $this->model_catalog_simplepars->getSettingBrowser($data["dn_id"]);
        if (isset($this->request->post["go_show"])) {
            if (empty($this->request->post["view_href"])) {
                if (empty($this->request->post["view_href2"])) {
                    $data["error"] = "Не выбрана ссылка для пред просмотра.";
                } else {
                    $this->request->post["view_href"] = $this->request->post["view_href2"];
                    $data["view_href"] = $this->request->post["view_href"];
                    $l[] = $this->request->post["view_href"];
                    $data["page_code"] = $this->model_catalog_simplepars->xmlCutsAnswerFromCurl($l, $data["dn_id"]);
                }
            } else {
                $data["view_href"] = $this->request->post["view_href"];
                $l[] = $this->request->post["view_href"];
                $data["page_code"] = $this->model_catalog_simplepars->xmlCutsAnswerFromCurl($l, $data["dn_id"]);
            }
        }
        $this->response->setOutput($this->load->view("catalog/simplepars_splitxml" . $adap["exten"], $data));
    }
    public function browser()
    {
        $adap = $this->adap();
        $data["adap"] = $adap;
        $data["dn_id"] = (int) $this->request->get["dn_id"];
        $this->document->setTitle("Настройка запросов - SimplePars");
        $this->load->model("catalog/simplepars");
        $data["header"] = $this->load->controller("common/header");
        $data["column_left"] = $this->load->controller("common/column_left");
        $data["footer"] = $this->load->controller("common/footer");
        $data["breadcrumbs"] = $this->breadcrumbs($adap);
        if (isset($this->session->data["success"])) {
            $data["success"] = $this->session->data["success"];
            unset($this->session->data["success"]);
        } else {
            $data["success"] = "";
        }
        if (isset($this->session->data["error"])) {
            $data["error"] = $this->session->data["error"];
            unset($this->session->data["error"]);
        }
        $data["mpage"] = $this->mPage();
        $data["browser"] = $this->model_catalog_simplepars->getSettingBrowser($data["dn_id"]);
        $data["proxy_list"] = $this->model_catalog_simplepars->getProxyListToPage($data["dn_id"]);
        $data["setting"] = $this->model_catalog_simplepars->getSetting($data["dn_id"]);
        if (isset($this->request->post["save_browser"])) {
            $this->model_catalog_simplepars->seveBrowser($this->request->post, $data["dn_id"]);
            $this->response->redirect($this->url->link("catalog/simplepars/browser", $adap["token"] . "&dn_id=" . $data["dn_id"], true));
        }
        if (isset($this->request->post["save_proxy_list"])) {
            $this->model_catalog_simplepars->saveProxyList($this->request->post, $data["dn_id"]);
            $this->response->redirect($this->url->link("catalog/simplepars/browser", $adap["token"] . "&dn_id=" . $data["dn_id"], true));
        }
        if (isset($this->request->post["clear_proxy_list"])) {
            $this->model_catalog_simplepars->clearProxyList($data["dn_id"]);
            $this->response->redirect($this->url->link("catalog/simplepars/browser", $adap["token"] . "&dn_id=" . $data["dn_id"], true));
        }
        if (isset($this->request->post["reset_proxy_list"])) {
            $this->model_catalog_simplepars->resetProxyList($data["dn_id"]);
            $this->response->redirect($this->url->link("catalog/simplepars/browser", $adap["token"] . "&dn_id=" . $data["dn_id"], true));
        }
        $this->response->setOutput($this->load->view("catalog/simplepars_browser" . $adap["exten"], $data));
    }
    public function share()
    {
        $adap = $this->adap();
        $data["adap"] = $adap;
        $data["dn_id"] = (int) $this->request->get["dn_id"];
        $this->document->setTitle("Импорт/Экспорт Настроек - SimplePars");
        $this->load->model("catalog/simplepars");
        $data["header"] = $this->load->controller("common/header");
        $data["column_left"] = $this->load->controller("common/column_left");
        $data["footer"] = $this->load->controller("common/footer");
        if (isset($this->session->data["error"])) {
            $data["error"] = $this->session->data["error"];
            unset($this->session->data["error"]);
        } else {
            $data["error"] = "";
        }
        if (isset($this->session->data["success"])) {
            $data["success"] = $this->session->data["success"];
            unset($this->session->data["success"]);
        } else {
            $data["success"] = "";
        }
        $data["breadcrumbs"] = $this->breadcrumbs($adap);
        $data["mpage"] = $this->mPage();
        $data["setting"] = $this->model_catalog_simplepars->getSetting($data["dn_id"]);
        if (isset($this->request->post["dw_form"])) {
            $data["file_json"] = $this->model_catalog_simplepars->getExportForm((int) $this->request->post["links"], $data["dn_id"]);
            $this->response->addheader("Pragma: public");
            $this->response->addheader("Expires: 0");
            $this->response->addheader("Content-Description: File Transfer");
            $this->response->addheader("Content-Type: application/octet-stream");
            $this->response->addheader("Content-Disposition: attachment; filename=\"SPsetting-" . (int) $data["dn_id"] . ".json\"");
            $this->response->addheader("Content-Transfer-Encoding: binary");
            $this->response->setOutput($data["file_json"]);
        }
        if (isset($this->request->post["sub_import"])) {
            if (is_uploaded_file($this->request->files["import"]["tmp_name"])) {
                if ($this->request->files["import"]["type"] === "application/json") {
                    $form = file_get_contents($this->request->files["import"]["tmp_name"]);
                    if ($form) {
                        $this->model_catalog_simplepars->importFrom($form, $data["dn_id"]);
                        $this->response->redirect($this->url->link("catalog/simplepars/share", $adap["token"] . "&dn_id=" . $data["dn_id"], true));
                    } else {
                        $data["error"] = " Не выбран файл для загрузки";
                    }
                } else {
                    $data["error"] = " Неправильный формат файла настроек.";
                }
            } else {
                $data["error"] = " Файл не загружен.";
            }
        }
        if (!isset($this->request->post["dw_form"])) {
            $this->response->setOutput($this->load->view("catalog/simplepars_share" . $adap["exten"], $data));
        }
    }

    public function act() {
        $adap = $this->adap();
        $data["adap"] = $adap;
        $this->document->setTitle("Активация модуля - SimplePars");
        $this->load->model("catalog/simplepars");
        $data["header"] = $this->load->controller("common/header");
        $data["column_left"] = $this->load->controller("common/column_left");
        $data["footer"] = $this->load->controller("common/footer");
        $pars = $this->db->query("SELECT * FROM `" . DB_PREFIX . "pars`");
        $data["type_key"] = $pars->row["key_lic"];
        $data["breadcrumbs"] = $this->breadcrumbs( $adap );
        if ( isset( $this->request->post["activ"] ) ) {
            $code = "true";
            $this->response->redirect( $this->url->link( "catalog/simplepars", $adap["token"], true ) );
        }

        if (isset($this->session->data["error"])) {
            $data["error"] = $this->session->data["error"];
            unset($this->session->data["error"]);
        } else {
            $data["error"] = "";
        }
        if (isset($this->session->data["success"])) {
            $data["success"] = $this->session->data["success"];
            unset($this->session->data["success"]);
        } else {
            $data["success"] = "";
        }
        if (isset($this->session->data["success_act"])) {
            $data["success_act"] = $this->session->data["success_act"];
            unset($this->session->data["success_act"]);
        } else {
            $data["success_act"] = "";
        }
        $this->response->setOutput($this->load->view("catalog/simplepars_act" . $adap["exten"], $data));
    }
    public function cron()
    {
        $adap = $this->adap();
        $data["adap"] = $adap;
        $this->document->setTitle("Менеджер заданий (CRON) - SimplePars");
        $this->load->model("catalog/simplepars");
        $data["header"] = $this->load->controller("common/header");
        $data["column_left"] = $this->load->controller("common/column_left");
        $data["footer"] = $this->load->controller("common/footer");
        $data["breadcrumbs"] = $this->breadcrumbs($adap);
        if (isset($this->session->data["success"])) {
            $data["success"] = $this->session->data["success"];
            unset($this->session->data["success"]);
        } else {
            $data["success"] = "";
        }
        if (isset($this->session->data["error"])) {
            $data["error"] = $this->session->data["error"];
            unset($this->session->data["error"]);
        }
        $cronpage = $this->model_catalog_simplepars->getCronPageInfo();
        $data["cron_permit"] = $cronpage["cron_permit"];
        $data["cron_button"] = $cronpage["cron_button"];
        $data["crons"] = $cronpage["crons"];
        $data["dn_list"] = $cronpage["dn_list"];
        $data["time_machin"] = $cronpage["time_machin"];
        $data["select_time"] = $cronpage["select_time"];
        $data["user_times"] = $cronpage["user_times"];
        $data["patterns_json"] = $cronpage["patterns_json"];
        $data["patterns"] = $cronpage["patterns"];
        $data["tools_last_key"] = $cronpage["tools_last_key"];
        $data["href_dn"] = $this->url->link("catalog/simplepars/grab", $adap["token"] . "&dn_id=", true);
        if (!empty($this->request->post["cron_permit"])) {
            $this->model_catalog_simplepars->cronOnOff($this->request->post);
            $this->response->redirect($this->url->link("catalog/simplepars/cron", $adap["token"], true));
        }
        if (!empty($this->request->post["cron_add"])) {
            $this->model_catalog_simplepars->cronAddTask($this->request->post);
            $this->response->redirect($this->url->link("catalog/simplepars/cron", $adap["token"], true));
        }
        if (!empty($this->request->post["save"])) {
            $this->model_catalog_simplepars->saveFormCron($this->request->post);
            $this->response->redirect($this->url->link("catalog/simplepars/cron", $adap["token"], true));
        }
        if (!empty($this->request->post["task_del"])) {
            $this->model_catalog_simplepars->cronDelTask($this->request->post);
            $this->response->redirect($this->url->link("catalog/simplepars/cron", $adap["token"], true));
        }
        if (!empty($this->request->post["rest_task"])) {
            $this->model_catalog_simplepars->cronRestartTaskFromUser($this->request->post["rest_task"]);
            $this->response->redirect($this->url->link("catalog/simplepars/cron", $adap["token"], true));
        }
        $this->response->setOutput($this->load->view("catalog/simplepars_cron" . $adap["exten"], $data));
    }
    public function ajax()
    {
        $adap = $this->adap();
        $data["adap"] = $adap;
        $data["dn_id"] = (int) $this->request->get["dn_id"];
        $this->load->model("catalog/simplepars");
        if (!empty($this->request->get["who"])) {
            if ($this->request->get["who"] == "paramsetup") {
                if (isset($this->request->post["act"])) {
                    $this->request->post["act"] = trim($this->request->post["act"]);
                    if ($this->request->post["act"] == "new") {
                        $param = $this->model_catalog_simplepars->addParamPars($this->request->post, $this->request->get["dn_id"]);
                        exit(json_encode($param));
                    }
                    $this->model_catalog_simplepars->saveParamPars($this->request->post);
                    exit("save_param");
                }
                if (isset($this->request->post["get_param_id"])) {
                    $activ_param = $this->model_catalog_simplepars->getActivParam($this->request->post["get_param_id"]);
                    exit(json_encode($activ_param));
                }
                if (!empty($this->request->post["piece_code"])) {
                    $show_code = $this->model_catalog_simplepars->showPieceCode($this->request->post, $data["dn_id"]);
                    exit($show_code["page_code"]);
                }
                if (!empty($this->request->post["do"]) && $this->request->post["do"] == "cache_page") {
                    $this->model_catalog_simplepars->changeTypeCaching($this->request->post["cache_page"], $data["dn_id"]);
                    exit("Выбор метода кеширования сохранен");
                }
                if (!empty($this->request->post["do"]) && $this->request->post["do"] == "pre_view_syntax") {
                    $this->model_catalog_simplepars->changeSelectSyntax($this->request->post["pre_view_syntax"], $data["dn_id"]);
                    exit("Выбор подсветки синтаксиса сохранен");
                }
                if (!empty($this->request->post["do"]) && $this->request->post["do"] == "pre_view_param") {
                    $this->model_catalog_simplepars->changeSelectPreview($this->request->post["pre_view_param"], $data["dn_id"]);
                    exit("Выбор подсветки синтаксиса сохранен");
                }
            } else {
                if ($this->request->get["who"] == "logs") {
                    if ($this->request->get["do"] == "get_logs") {
                        $logs = $this->model_catalog_simplepars->getLogs($data["dn_id"]);
                        exit($logs);
                    }
                } else {
                    if ($this->request->get["who"] == "tools") {
                        $this->load->model("tool/image");
                        if ($this->request->get["do"] == "filter") {
                            $answ = array();
                            $temp = $this->model_catalog_simplepars->toolFilterToPage($this->request->post, $data["dn_id"]);
                            $pagination = new Pagination();
                            $pagination->total = $temp["total"];
                            $pagination->page = (int) $this->request->post["page"];
                            $pagination->limit = (int) $this->request->post["page_count"];
                            $pagination->url = $this->url->link("catalog/simplepars/tools", $adap["token"] . "&dn_id=" . $data["dn_id"] . "&page={page}");
                            $answ["pagination"] = $this->model_catalog_simplepars->toolRenderPage($pagination->render());
                            foreach ($temp["products"] as &$product) {
                                $product["url_in"] = $this->url->link("catalog/product/edit", $adap["token"] . "&product_id=" . $product["product_id"], true);
                            }
                            $answ["totla"] = $temp["total"];
                            $answ["back_cod"] = $temp["back_cod"];
                            $answ["products"] = $temp["products"];
                            exit(json_encode($answ));
                        } else {
                            if ($this->request->get["do"] == "action") {
                                $this->model_catalog_simplepars->toolControlerFunction($this->request->post, $data["dn_id"], "user");
                            }
                        }
                    } else {
                        if ($this->request->get["who"] == "listurl") {
                            if ($this->request->get["do"] == "filter") {
                                $answ = array();
                                $temp = $this->model_catalog_simplepars->urlFilterToPage($this->request->post, $data["dn_id"]);
                                $pagination = new Pagination();
                                $pagination->total = $temp["total"];
                                $pagination->page = (int) $this->request->post["page"];
                                $pagination->limit = (int) $this->request->post["page_count"];
                                $pagination->url = $this->url->link("catalog/simplepars/listurl", $adap["token"] . "&dn_id=" . $data["dn_id"] . "&page={page}");
                                $answ["pagination"] = $this->model_catalog_simplepars->toolRenderPage($pagination->render());
                                $answ["totla"] = $temp["total"];
                                $answ["urls"] = $temp["urls"];
                                exit(json_encode($answ));
                            }
                            if ($this->request->get["do"] == "action") {
                                $this->model_catalog_simplepars->urlControlerFunction($this->request->post, $data["dn_id"]);
                            }
                        } else {
                            if ($this->request->get["who"] == "splitxml") {
                                if (!empty($this->request->post["show_test"])) {
                                    $this->model_catalog_simplepars->xmlSaveGran($this->request->post, $data["dn_id"]);
                                    $show_code = $this->model_catalog_simplepars->xmlShowPieceCode($this->request->post, $data["dn_id"]);
                                    exit($show_code);
                                }
                                if (!empty($this->request->post["save_gran"])) {
                                    $this->model_catalog_simplepars->xmlSaveGran($this->request->post, $data["dn_id"]);
                                    exit("Граница деления XML на разные товары сохранена");
                                }
                                if (!empty($this->request->post["save_cache_page"])) {
                                    $this->model_catalog_simplepars->changeTypeCaching($this->request->post["cache_page"], $data["dn_id"]);
                                }
                            } else {
                                if ($this->request->get["who"] == "get_urls") {
                                    if (empty($this->request->post["links_restart"])) {
                                        $this->model_catalog_simplepars->saveLinkListAndError($this->request->post, $data["dn_id"]);
                                        $pars_url = $this->model_catalog_simplepars->getUrlToPars($data["dn_id"], $this->request->post["link_list"], $this->request->post["link_error"]);
                                        exit(json_encode($pars_url));
                                    }
                                    $this->model_catalog_simplepars->restLinkToPars($this->request->post, $data["dn_id"]);
                                    exit(json_encode("Рестарт ссылок произведен"));
                                }
                                if ($this->request->get["who"] == "get_urls_sen") {
                                    if (empty($this->request->post["links_restart"])) {
                                        $pars_url = $this->model_catalog_simplepars->getUrlSenToPars($data["dn_id"]);
                                        exit(json_encode($pars_url));
                                    }
                                    $this->model_catalog_simplepars->restSenLinkToPars($data["dn_id"]);
                                    exit(json_encode("Рестарт ссылок произведен"));
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    public function parsajax()
    {
        $data["dn_id"] = (int) $this->request->get["dn_id"];
        $this->load->model("catalog/simplepars");
        if (!empty($this->request->get["who"])) {
            if ($this->request->get["who"] == "grab") {
                if ($this->request->get["i"] == 1) {
                    $this->model_catalog_simplepars->SeveFormGrab($this->request->post, $data["dn_id"]);
                }
                $this->model_catalog_simplepars->grabControl($this->request->get["i"], $data["dn_id"]);
            } else {
                if ($this->request->get["who"] == "pr_csv") {
                    $this->model_catalog_simplepars->controlParsDataToCsv($data["dn_id"]);
                } else {
                    if ($this->request->get["who"] == "pr_im") {
                        $this->model_catalog_simplepars->startParsToIm($data["dn_id"]);
                    } else {
                        if ($this->request->get["who"] == "br_pr") {
                            $this->model_catalog_simplepars->startCheckProxy($data["dn_id"]);
                        } else {
                            if ($this->request->get["who"] == "pr_cache") {
                                if ($this->request->get["i"] == 1) {
                                    $this->model_catalog_simplepars->saveCacheForm($this->request->post, $data["dn_id"]);
                                }
                                $this->model_catalog_simplepars->controlParsToCache($data["dn_id"]);
                            } else {
                                if ($this->request->get["who"] == "pr_xml") {
                                    $this->model_catalog_simplepars->controlParsToXml($data["dn_id"]);
                                } else {
                                    exit(json_encode("error pars who"));
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    public function cronstart()
    {
        $this->load->model("catalog/simplepars");
        $this->model_catalog_simplepars->cronStart();
    }
    private function validateForm()
    {
        if (!$this->user->hasPermission("modify", "catalog/simplepars")) {
            $this->error["warning"] = $this->language->get("error_permission");
        }
        if (!$this->error) {
            return true;
        }
        return false;
    }
    public function mPage()
    {
        $adap = $this->adap();
        $data["adap"] = $adap;
        $data["dn_id"] = (int) $this->request->get["dn_id"];
        $mpage[1] = array("active" => "", "title" => "<i class=\"fa fa-eye\" aria-hidden=\"true\"></i> Сбор ссылок", "href" => $this->url->link("catalog/simplepars/grab", $adap["token"] . "&dn_id=" . $data["dn_id"], true));
        $mpage[2] = array("active" => "", "title" => "<i class=\"fa fa-tasks\" aria-hidden=\"true\"></i> Настройки парсинга", "href" => $this->url->link("catalog/simplepars/paramsetup", $adap["token"] . "&dn_id=" . $data["dn_id"], true));
        $mpage[3] = array("active" => "", "title" => "<i class=\"fa fa-search\" aria-hidden=\"true\"></i> Поиск/Замена", "href" => $this->url->link("catalog/simplepars/replace", $adap["token"] . "&dn_id=" . $data["dn_id"], true));
        $mpage[4] = array("active" => "", "title" => "<i class=\"fa fa-cart-arrow-down\" aria-hidden=\"true\"></i> Парсинга в ИМ", "href" => $this->url->link("catalog/simplepars/productsetup", $adap["token"] . "&dn_id=" . $data["dn_id"], true));
        $mpage[5] = array("active" => "", "title" => "<i class=\"fa fa-file-text\" aria-hidden=\"true\"></i> CSV/Парсинг", "href" => $this->url->link("catalog/simplepars/createcsv", $adap["token"] . "&dn_id=" . $data["dn_id"], true));
        $mpage[6] = array("active" => "", "title" => "<i class=\"fa fa-pencil\" aria-hidden=\"true\"></i> Редактор товаров", "href" => $this->url->link("catalog/simplepars/tools", $adap["token"] . "&dn_id=" . $data["dn_id"], true));
        $mpage[7] = array("active" => "", "title" => "<i class=\"fa fa-list\" aria-hidden=\"true\"></i> Менеджер URL", "href" => $this->url->link("catalog/simplepars/listurl", $adap["token"] . "&dn_id=" . $data["dn_id"], true));
        $mpage[8] = array("active" => "", "title" => "<i class=\"fa fa-bug\" aria-hidden=\"true\"></i> Логи", "href" => $this->url->link("catalog/simplepars/logs", $adap["token"] . "&dn_id=" . $data["dn_id"], true));
        $mpage[9] = array("active" => "", "title" => "<i class=\"fa fa-file-excel-o\" aria-hidden=\"true\"></i> Обработчик XML", "href" => $this->url->link("catalog/simplepars/splitxml", $adap["token"] . "&dn_id=" . $data["dn_id"], true));
        $mpage[10] = array("active" => "", "title" => "<i class=\"fa fa-chrome\" aria-hidden=\"true\"></i> Настройка запросов", "href" => $this->url->link("catalog/simplepars/browser", $adap["token"] . "&dn_id=" . $data["dn_id"], true));
        $mpage[11] = array("active" => "", "title" => "<i class=\"fa fa-exchange\" aria-hidden=\"true\"></i> Импорт/Экспорт Настроек", "href" => $this->url->link("catalog/simplepars/share", $adap["token"] . "&dn_id=" . $data["dn_id"], true));
        if ($this->request->get["route"] == "catalog/simplepars/grab") {
            $mpage[1]["active"] = "active";
        } else {
            if ($this->request->get["route"] == "catalog/simplepars/paramsetup") {
                $mpage[2]["active"] = "active";
            } else {
                if ($this->request->get["route"] == "catalog/simplepars/replace") {
                    $mpage[3]["active"] = "active";
                } else {
                    if ($this->request->get["route"] == "catalog/simplepars/productsetup") {
                        $mpage[4]["active"] = "active";
                    } else {
                        if ($this->request->get["route"] == "catalog/simplepars/createcsv") {
                            $mpage[5]["active"] = "active";
                        } else {
                            if ($this->request->get["route"] == "catalog/simplepars/tools") {
                                $mpage[6]["active"] = "active";
                            } else {
                                if ($this->request->get["route"] == "catalog/simplepars/listurl") {
                                    $mpage[7]["active"] = "active";
                                } else {
                                    if ($this->request->get["route"] == "catalog/simplepars/logs") {
                                        $mpage[8]["active"] = "active";
                                    } else {
                                        if ($this->request->get["route"] == "catalog/simplepars/splitxml") {
                                            $mpage[9]["active"] = "active";
                                        } else {
                                            if ($this->request->get["route"] == "catalog/simplepars/browser") {
                                                $mpage[10]["active"] = "active";
                                            } else {
                                                if ($this->request->get["route"] == "catalog/simplepars/share") {
                                                    $mpage[11]["active"] = "active";
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return $mpage;
    }
    public function adap()
    {
        $n_vers = 1;
        if (empty($this->session->data["token"])) {
            $adap["token_t"] = "user_token=";
            $adap["token_v"] = $this->session->data["user_token"];
            $adap["token"] = $adap["token_t"] . $adap["token_v"];
            $adap["exten"] = "";
            $adap["n_vers"] = $n_vers;
        } else {
            $adap["token_t"] = "token=";
            $adap["token_v"] = $this->session->data["token"];
            $adap["token"] = $adap["token_t"] . $adap["token_v"];
            $adap["exten"] = ".tpl";
            $adap["n_vers"] = $n_vers;
        }
        return $adap;
    }
    public function breadcrumbs($adap)
    {
        $breadcrumbs = array();
        $breadcrumbs[] = array("text" => "Главная", "href" => $this->url->link("common/dashboard", $adap["token"], true));
        $breadcrumbs[] = array("text" => "SimplePars " . $this->model_catalog_simplepars->simpleParsVersion(), "href" => $this->url->link("catalog/simplepars", $adap["token"], true));
        return $breadcrumbs;
    }
    
    public function wtf($data)
    {
    }
    
}

?>