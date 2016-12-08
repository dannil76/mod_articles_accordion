<?php defined('_JEXEC') or die;

// Include the helper functions only once
JLoader::register( 'ModArticlesAccordionHelper', __DIR__ . '/helper.php' );

$input = JFactory::getApplication()->input;

$idbase = $params->get('catid');

$cacheparams               = new stdClass;
$cacheparams->cachemode    = 'id';
$cacheparams->class        = 'ModArticlesAccordionHelper';
$cacheparams->method       = 'getList';
$cacheparams->methodparams = $params;
$cacheparams->modeparams   = md5( serialize( array( $idbase, $module->module, $module->id ) ) );

$list = JModuleHelper::moduleCache( $module, $params, $cacheparams );

if( !empty($list) )
{
	$moduleclass_sfx	= htmlspecialchars( $params->get('moduleclass_sfx'), ENT_COMPAT, 'UTF-8' );
	$item_heading		= $params->get('item_heading');

	require JModuleHelper::getLayoutPath( 'mod_articles_accordion', $params->get('layout', 'default') );
}
