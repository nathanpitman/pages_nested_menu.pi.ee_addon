<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$plugin_info = array(
    'pi_name'           => 'Pages &ndash; Nested Menu',
    'pi_version'        => '2.0.0',
    'pi_author'         => 'Original development by Mark Huot. Updated for EE2.x by Nathan Pitman',
    'pi_author_url'     => 'https://github.com/ninefour/pages_nested_menu.pi.ee_addon',
    'pi_description'    => 'Nested List of Pages',
    'pi_usage'          => Pages_nested_menu::usage()
);



class Pages_nested_menu {

    var $return_data = "";
    
    var $str;
    var $pages;
    var $titles;
    var $params;
    var $ordered;
    
    var $cfields;
    var $nesting_path;
    var $page_counter = 1;
    
    //  *********************************************
    //  Constructor!
    //  *********************************************
    //  It's funny, I can't say that without an
    //  exclamation point.
    //  ---------------------------------------------
    function Pages_nested_menu($str = '')
    {
        //global $DB, $DSP, $FNS, $IN, $PREFS, $SESS, $TMPL;
        $this->EE =& get_instance();
        
        $this->return_data = '';
        
        //  =============================================
        //  Get Plugin Content
        //  =============================================
        if ($str == '') $str = $this->EE->TMPL->tagdata;
        $this->str = $str;
        
        
        //  =============================================
        //  Get Site Pages
        //  =============================================
        $pages = $this->EE->config->item('site_pages');
        
        natcasesort($pages[1]['uris']);

		//  =============================================
		//  Are Any Blank?
		//  =============================================
		unset($pages['uris']['']);

        $this->pages = $pages;
        
        //  =============================================
        //  Are There Pages?
        //  =============================================
        //  If not we'll return out and skip all this
        //  silly processing.
        //  ---------------------------------------------
        if(count($this->pages[1]['uris']) == 0) return $str;
        
        //  =============================================
        //  Get Full Titles
        //  =============================================
        if(!isset($this->EE->session->cache['mh_pages_exp_channel_titles_data']))
        {
            $this->EE->session->cache['mh_pages_exp_channel_titles_data'] = $this->EE->db->query('SELECT * FROM exp_channel_titles t, exp_channel_data d WHERE t.entry_id IN ('.implode(',', array_keys($pages[1]['uris'])).') AND t.entry_id=d.entry_id ORDER BY t.entry_id='.implode(' DESC, t.entry_id=', array_keys($pages[1]['uris'])).' DESC');
        }
        
        //  =============================================
        //  Get Custom Fields
        //  =============================================
        if(!isset($this->EE->session->cache['mh_pages_exp_channel_fields']))
        {
            $this->EE->session->cache['mh_pages_exp_channel_fields'] = $this->EE->db->query('SELECT * FROM exp_channel_fields');
        }
        
        foreach($this->EE->session->cache['mh_pages_exp_channel_fields']->result_array as $cfield)
        {
            $this->cfields[$cfield['field_id']] = $cfield['field_name'];
        }
        
        //  =============================================
        //  Rewrite Titles Table
        //  =============================================
        foreach($this->EE->session->cache['mh_pages_exp_channel_titles_data']->result_array as $key => $result)
        {
            foreach($result as $id => $value)
            {
                if(substr($id, 0, 9) == 'field_id_')
                {
                    $this->EE->session->cache['mh_pages_exp_channel_titles_data']->result[$key][$this->cfields[substr($id, 9)]] = $value;
                }
            }
        }
        
        //  =============================================
        //  Store Titles
        //  =============================================
        $this->titles = $this->EE->session->cache['mh_pages_exp_channel_titles_data'];
        
        //  =============================================
        //  Are we ordering by a field or the title?
        //  =============================================
        $order = 'title';
        if($this->EE->TMPL->fetch_param('order')!=false)
        {
            if(is_numeric($this->EE->TMPL->fetch_param('order')))
            {
                $order = 'field_id_'.$this->EE->TMPL->fetch_param('order');
            }
            else
            {
                $order = $this->EE->TMPL->fetch_param('order');
            }
        }
        
        //  =============================================
        //  Parse Status Parameter
        //  =============================================
        $statuses = array_filter(preg_split('/\s*\|\s*/', $this->EE->TMPL->fetch_param('status')));
        $statuses = array_map('strtolower', $statuses);
        $statuses_state = 1;
        if(count($statuses) > 0)
        {
            if(substr($statuses['0'], 0, 4) == 'not ')
            {
                $statuses['0'] = trim(substr($statuses['0'], 3));
                $statuses_state = -1;
                $statuses[] = 'closed';
            }
        }
        else
        {
            $statuses[] = 'open';
        }
        $this->params['statuses'] = $statuses;
        $this->params['statuses_state'] = $statuses_state;
        
        //  =============================================
        //  Parse Root Parameter
        //  =============================================
        $root = false;
        if($this->EE->TMPL->fetch_param('root') !== false)
        {
            $root = $this->EE->TMPL->fetch_param('root');
            
            //  =============================================
            //  Fix Current Pages
            //  =============================================
            if($root == '{page_url}' || $root == 'page_url')
            {
                $root = '/'.implode('/', $IN->SEGS).'/';
            }
            
            //  =============================================
            //  Make Sure Root Has a /
            //  =============================================
            $root = '/'.preg_replace('/^\/|^&#47;|\/$|&#47;$/', '', $root).'/';
            $root = str_replace('&#47;', '/', $root);
        }
        $this->params['root'] = $root;
        
        //  =============================================
        //  Order Pages
        //  =============================================
        $title_counter = 0;
        $ordered = array();
        foreach($pages[1]['uris'] as $entry_id => $uri)
        {
            
            $base = &$ordered['children'];
            $segs = array_filter(preg_split('/\//', $uri));
			if(count($segs) == 0) $segs[] = '';
			
			foreach($segs as $seg)
            {
            
	            $tmp = $this->titles->result_array();
            
                if (! isset($base[$seg]) && isset($tmp)) 
                {
                    
                    $base[$seg]['order'] = (String)$this->element($order, $tmp[$title_counter], $tmp[$title_counter]['title']);
                    $base[$seg]['exp_channel_titles'] = $tmp[$title_counter];
                    $base[$seg]['children'] = array();
                }
                
                $base = &$base[$seg]['children'];
            }
            
            unset($tmp);
            $title_counter++;
        }
        
        $this->ordered = nest_sort($ordered['children']);
        
        //  =============================================
        //  Get HTML
        //  =============================================
        $this->get_html($this->ordered);
        
        //  =============================================
        //  Add UL's if Necessary
        //  =============================================
        if($this->EE->TMPL->fetch_param('include_ul') != 'no')
        {
            $this->return_data = '<ul>'.$this->return_data.'</ul>';
        }
    }
    
    //  *********************************************
    //  Get HTML
    //  *********************************************
    //  Functionized, so we can call the HTML or
    //  maybe a select list
    //  ---------------------------------------------
    function get_html( $pages )
    {
        
        foreach($pages as $page)
        {
            //  =============================================
            //  We're politically correct; assume all pages
            //  are displayed until proven un-displayed
            //  =============================================
            $displayed = true;
            
            //  =============================================
            //  Track Path to Page
            //  =============================================
            $this->nesting_path[] = $page['exp_channel_titles']['url_title'];
            $page_path = $this->pages[1]['uris'][$page['exp_channel_titles']['entry_id']];
            
            //  =============================================
            //  Check if Actually Displayed
            //  =============================================
            $displayed = $this->is_displayed($page);
            
            //  =============================================
            //  Print out Displayed Pages
            //  =============================================
            if($displayed)
            {
                //  =============================================
                //  Populate Variables
                //  =============================================
                $variables = array();
                foreach($page['exp_channel_titles'] as $key=>$value)
                {
                    $variables[$key] = $value;
                }
                $variables['page_url'] = $this->EE->functions->create_url($page_path, 1, 0);
                $variables['depth'] = count($this->nesting_path);
                $variables['has_children'] = (isset($page['children']) && count($page['children']) > 0)?'yes':'no';
                $variables['displayed'] = ($displayed)?'yes':'no';
                $variables['count'] = $this->page_counter++;
                $variables = $this->add_delimiters($variables);
                
                //  =============================================
                //  Open LI
                //  =============================================
                
                if(preg_match('/^\s*<li/s', preg_replace('/'.LD.'if.*?'.RD.'.*?'.LD.'&#47;if'.RD.'/s', '', $this->str)) === 0)
                {
                    $this->return_data .= '<li>';
                }
                
                //  =============================================
                //  Replace Variables
                //  =============================================
                $this->return_data .= str_replace(array_keys($variables), $variables, $this->str);
            }
            
            //  =============================================
            //  Check Drilling Down
            //  =============================================
            //  Normally if a page isn't displayed we don't
            //  need to check it's children, because they
            //  won't be displayed either.  However, if our
            //  root starts us halfway down the tree, we'll
            //  want to work our way in there.  Therefore,
            //  we have to check if we're starting down the
            //  path to the passed in root, if so, continue
            //  checking, if not, we can skip over this page.
            //  phew.
            //  ---------------------------------------------
            $drilling_down = preg_match('#^'.$page_path.'#', $this->params['root']) !== 0;
            
            //  =============================================
            //  Begin Nesting Children
            //  =============================================
            if(($displayed || $drilling_down) && isset($page['children']) !== false && count($page['children']) > 0)
            {
                //  =============================================
                //  Are there any Displayed Children?
                //  =============================================
                $children_displayed = false;
                foreach($page['children'] as $child)
                {
					$this->nesting_path[] = $page['exp_channel_titles']['url_title'];
                    if($this->is_displayed($child) === true)
                    {
                        $children_displayed = true;
						array_pop($this->nesting_path);
                        break;
                    }
					array_pop($this->nesting_path);
                }
                
                //  =============================================
                //  If there are, show them
                //  =============================================
                if($children_displayed || $drilling_down)
                {
                    if($displayed)
                    {
                        if(preg_match('/<&#47;li>\s*$/s', $this->return_data) !== 0)
                        {
                            $this->return_data = preg_replace('/<&#47;li>\s*$/s', '', $this->return_data);
                        }
                        
                        $this->return_data .= '<ul>';
                    }
                    
                    $this->get_html($page['children']);
                    
                    if($displayed)
                    {
                        $this->return_data .= '<&#47;ul>';
                    }
                }
            }
            
            //  =============================================
            //  Close LI
            //  =============================================
            if($displayed && preg_match('/<&#47;li>\s*$/s', preg_replace('/'.LD.'if.*?'.RD.'.*?'.LD.'&#47;if'.RD.'/s', '', $this->str)) === 0)
            {
                $this->return_data .= '<&#47;li>'."\r";
            }
            
            //  =============================================
            //  Pop Last Added Child Off
            //  =============================================
            $displayed = true;
            array_pop($this->nesting_path);
        }
    }
    
    //  *********************************************
    //  Is the Page Displayed?
    //  *********************************************
    //  Run through some status, depth, and root
    //  checks to be sure the page should be
    //  displayed
    //  ---------------------------------------------
    function is_displayed( $page )
    {
        global $TMPL;
        
        $displayed = true;
        
        //  =============================================
        //  Make sure we're in the path
        //  =============================================
        // $nesting_added = false;
        // if($this->nesting_path[count($this->nesting_path)-1] !== $page['exp_channel_titles']['url_title'])
        // {
        //     $this->nesting_path[] = $page['exp_channel_titles']['url_title'];
        //     $nesting_added = true;
        // }
        
        //  =============================================
        //  Check that we're within the status
        //  =============================================
        //  var_dump(((true?1:-1) * 1) == 1?'true':'false');
        if((in_array(strtolower($page['exp_channel_titles']['status']), $this->params['statuses'])?1:-1)*$this->params['statuses_state'] == -1)
        {
            $displayed = false;
        }
        
        //  =============================================
        //  Check if we're Below the Root
        //  =============================================
        if($this->params['root'] !== false)
        {
            $page_path = $this->pages[1]['uris'][$page['exp_channel_titles']['entry_id']];
            // var_dump('/^'.preg_quote($this->params['root'], '/').'/i, '.$page_path);
            if(preg_match('/^'.preg_quote($this->params['root'], '/').'/i', $page_path) === 0)
            {
                $displayed = false;
            }
            
            //  =============================================
            //  Check if we Should Include the Root
            //  =============================================
            else if(($this->EE->TMPL->fetch_param('include_root') == false || $this->EE->TMPL->fetch_param('include_root') == 'no') && $this->params['root'] == $page_path)
            {
                $displayed = false;
            }
        }
        
        //  =============================================
        //  Check That We're Above the Depth
        //  =============================================
        if($this->EE->TMPL->fetch_param('depth') !== false)
        {
            $depth = $this->EE->TMPL->fetch_param('depth');
            $depth += count(array_filter(preg_split('/\//', $this->params['root'])));
            
            if(count($this->nesting_path) > $depth)
            {
                $displayed = false;
            }
        }
        
        //  =============================================
        //  Remove the path, if we just added it
        //  =============================================
        // if($nesting_added === true)
        // {
        //     array_pop($this->nesting_path);
        // }
        
        return $displayed;
    }
    
    //  *********************************************
    //  Add Delimiters to Variables
    //  *********************************************
    //  Because there's no norm we'll split this out
    //  to make it easier to change later
    //  ---------------------------------------------
    function add_delimiters( $ary )
    {
        $return = array();
        foreach($ary as $key => $value)
        {
            $return[LD.$key.RD] = $value;
            $return[LD.'pnm_'.$key.RD] = $value;
            $return['['.$key.']'] = $value;
        }
        return $return;;
    }
    
    //  *********************************************
    //  Element!
    //  *********************************************
    //  Thanks codeigniter!
    //  ---------------------------------------------
    function element( $key, $array, $default=false )
    {
        return (isset($array[$key])&&$array[$key]!='')?$array[$key]:$default;
    }
    
// ----------------------------------------
//  Plugin Usage
// ----------------------------------------

// This function describes how the plugin is used.
// Make sure and use output buffering

function usage()
{
ob_start(); 
?>
This plugin creates a list of your Pages.
<?php
$buffer = ob_get_contents();

ob_end_clean(); 

return $buffer;
}
/* END */
}

function nest_sort( $seq )
{
      
    if(!count($seq)) return $seq;
    
    $keys = array_keys($seq);
    
    if(isset($seq[$keys[0]]['children']))
    {
        $seq[$keys[0]]['children'] = nest_sort($seq[$keys[0]]['children']);
    }
    $k = $seq[$keys[0]];
    $x = $y = array();
    
    for($i=1; $i<count($keys); $i++)
    {
            if($seq[$keys[$i]]['order'] <= $k['order'])
            {
                    $x[$keys[$i]] = $seq[$keys[$i]];
            }
            else
            {
                    $y[$keys[$i]] = $seq[$keys[$i]];
            }
    }
    
    return nest_sort($x) + array($keys[0] => $k) + nest_sort($y);
}

?>
