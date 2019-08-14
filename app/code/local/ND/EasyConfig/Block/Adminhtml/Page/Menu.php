<?php 
class ND_EasyConfig_Block_Adminhtml_Page_Menu extends Mage_Adminhtml_Block_Page_Menu
{
    protected $_tabs = null;
    protected $_configMenu = null;
    
    protected function _construct()
    {
        parent::_construct();
        $this->prepareConfigMenu();
    }
    
    public function addTab($code, $config)
    {
        $tab = new Varien_Object($config);
        $tab->setId($code);
        $this->_tabs[$code] = $tab;
        return $this;
    }

    public function getTab($code)
    {
        if(isset($this->_tabs[$code])) {
            return $this->_tabs[$code];
        }

        return null;
    }
    
    public function addSection($code, $tabCode, $config)
    {
        if($tab = $this->getTab($tabCode)) {
            if(!$tab->getSections()) {
                $tab->setSections(new Varien_Data_Collection());
            }
            $section = new Varien_Object($config);
            $section->setId($code);
            $tab->getSections()->addItem($section);
        }
        return $this;
    }
    
    protected function _sort($a, $b)
    {
        return (int)$a->sort_order < (int)$b->sort_order ? -1 : ((int)$a->sort_order > (int)$b->sort_order ? 1 : 0);
    }
    
    public function prepareConfigMenu()
    {
        $websiteCode = $this->getRequest()->getParam('website');
        $storeCode = $this->getRequest()->getParam('store');
        $configFields = Mage::getSingleton('adminhtml/config');
        $sections = $configFields->getSections();
        $tabs     = (array)$configFields->getTabs()->children();
        $url = Mage::getModel('adminhtml/url');
        $tabMenu = $sectionMenu = array();
        $sections = (array)$sections;

        usort($sections, array($this, '_sort'));
        usort($tabs, array($this, '_sort'));

        foreach ($tabs as $tab) {
            $helperName = $configFields->getAttributeModule($tab);
            $label = Mage::helper($helperName)->__((string)$tab->label);
            
            $this->addTab($tab->getName(), array(
                'label' => $label,
                'class' => (string) $tab->class
            ));

            $tabMenu['name'][] = $tab->getName();
            $tabMenu['label'][] = $label;
        }

        $s=0;foreach ($sections as $section) {
            Mage::dispatchEvent('adminhtml_block_system_config_init_tab_sections_before', array('section' => $section));
            $hasChildren = $configFields->hasChildren($section, $websiteCode, $storeCode);

            $code = $section->getName();
            //$sectionAllowed = $this->checkSectionPermissions($code);

            $helperName = $configFields->getAttributeModule($section);
            $label = Mage::helper($helperName)->__((string)$section->label);
            if ($hasChildren) {
                $this->addSection($code, (string)$section->tab, array(
                    'class'     => (string)$section->class,
                    'label'     => $label,
                    'url'       => $url->getUrl('adminhtml/system_config', array('_current'=>true, 'section'=>$code)),
                ));
            }    
            /* For single menu without submenu sections */
            //$sectionMenu[$s]['name'] = $code;
            //$sectionMenu[$s]['label'] = $label;
            //$sectionMenu[$s]['url'] = $url->getUrl('*/*/*', array('_current'=>true, 'section'=>$code));
            $s++;
        }
        
        $n=0; foreach($this->_tabs as $tab){
            $this->_configMenu[$n] = $tab->getData();            
            if($tab->getSections()) {                
                $this->_configMenu[$n]['url'] = '#';
                $this->_configMenu[$n]['click'] = 'return false';
                foreach($tab->getSections() as $section) {
                    $this->_configMenu[$n]['children'][] = $section->getData();
                }
                unset($this->_configMenu[$n]['sections']);
            }
            $n++;
        }
    }
    
    public function getMenuLevel($menu, $level = 0)
    {                        
        $html = '<ul ' . (!$level ? 'id="nav"' : '') . '>' . PHP_EOL;
        foreach ($menu as $item) {         
            $isConfig = ($item['label']=='Configuration') ? true : false;
            if($isConfig) {
                $item['children'] = $this->_configMenu;
            }
            $html .= '<li ' . ((!empty($item['children']) || $isConfig) ? 'onmouseover="Element.addClassName(this,\'over\')" '
                . 'onmouseout="Element.removeClassName(this,\'over\')"' : '') . ' class="'
                . (!$level && !empty($item['active']) ? ' active' : '') . ' '
                . (!empty($item['children']) ? ' parent' : '')
                . (!empty($level) && !empty($item['last']) ? ' last' : '')
                . ' level' . $level . '"> <a href="' . $item['url'] . '" '
                . (!empty($item['title']) ? 'title="' . $item['title'] . '"' : '') . ' '
                . (!empty($item['click']) ? 'onclick="' . $item['click'] . '"' : '') . ' class="'
                . ($level === 0 && !empty($item['active']) ? 'active' : '') . '"><span>'
                . $item['label'] . '</span></a>' . PHP_EOL;

            if (!empty($item['children'])) {
                $html .= $this->getMenuLevel($item['children'], $level + 1);
            }
            $html .= '</li>' . PHP_EOL;
        }
        $html .= '</ul>' . PHP_EOL;

        return $html;
    }
}
