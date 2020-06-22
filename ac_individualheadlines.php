<?php
if (!defined('_PS_VERSION_'))
{
    exit;
}

class ac_individualheadlines extends Module
{
    public function __construct()
    {
        $this->name = 'ac_individualheadlines';
        $this->tab = 'front_office_features';
        $this->version = '1.0';
        $this->author = 'Mateusz Borowik';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = array(
            'min' => '1.7.1.0',
            'max' => _PS_VERSION_
        );
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Individual Category H1');
        $this->description = $this->l('Add Individual Category H1');
    }

    public function install()
    {
        if (
            !parent::install() or
            !$this->alterTable() or
            !$this->registerHook('actionCategoryFormBuilderModifier') or
            !$this->registerHook('actionAfterCreateCategoryFormHandler') or
            !$this->registerHook('actionAfterUpdateCategoryFormHandler')
        ) {
            return false;
        } 

        return true;
    }

    public function uninstall()
    {
        if (
            !parent::uninstall() or
            !$this->alterTable('remove')) {
            return false;
        }
        return true;
    }

    public function alterTable($method = 'add')
    {
        if ($method == 'add') {
            $sql = 'ALTER TABLE ' . _DB_PREFIX_ . 'category_lang ADD `individual_headlines` VARCHAR (255) NOT NULL';
        } else {
            $sql = 'ALTER TABLE ' . _DB_PREFIX_ . 'category_lang DROP COLUMN `individual_headlines`';
        }

        if (!Db::getInstance()->Execute($sql)){
            return false;
        }
        return true;
    }

    /**
     * @param array $params
     */
    public function hookActionCategoryFormBuilderModifier(array $params)
    {
        /** @var \Symfony\Component\Form\FormBuilder $formBuilder */
        $formBuilder = $params['form_builder'];

        $formBuilder->add(
            'individual_headlines',
            \PrestaShopBundle\Form\Admin\Type\TranslatableType::class,
            [
                'label' => $this->l('Individual H1') ,
                'required' => false,
                'type' => \Symfony\Component\Form\Extension\Core\Type\TextType::class
            ]
          );

        $languages = Language::getLanguages(false);
        $currentHeadlines = $this->getCurrentHeadlines($params);

        foreach ($languages as $lang)
        {
            $params['data']['individual_headlines'][$lang['id_lang']] = $currentHeadlines[$lang['id_lang']];
        }

        $formBuilder->setData($params['data']);
    }

    public function getCurrentHeadlines(array $params)
    {
        $categoryHeadlines = array();

        $records = Db::getInstance()->executeS('
            SELECT `id_lang`, `individual_headlines`
            FROM `' . _DB_PREFIX_ . 'category_lang`
            WHERE `id_category` = ' . (int)$params['id'] . ' AND `id_shop` = 1 
        ');

        foreach ($records as $record) {
            $categoryHeadlines[$record['id_lang']] = $record['individual_headlines'];
        }
        
        return $categoryHeadlines;

    }

    public function hookActionAfterCreateCategoryFormHandler(array $params)
    {
        $this->updateData($params['form_data'], $params);
    }

    public function hookActionAfterUpdateCategoryFormHandler(array $params)
    {
        $this->updateData($params['form_data'], $params);
    }

    protected function updateData(array $data, $params)
    {
        $languages = Language::getLanguages(false);
        foreach ($languages as $language)
        {
            Db::getInstance()->execute(
                "UPDATE `" . _DB_PREFIX_ . "category_lang`
                SET `individual_headlines` = '" . $data['individual_headlines'][$language['id_lang']] . "' 
                WHERE `id_category` = " . (int)$params['id'] . " AND `id_shop` = 1 AND `id_lang` = " . (int)$language['id_lang']
            );
        }
    }
}

