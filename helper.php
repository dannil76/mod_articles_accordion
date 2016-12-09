<?php defined('_JEXEC') or die;

$comPath = JPATH_SITE . '/components/com_content';
require_once $comPath . '/helpers/route.php';

JModelLegacy::addIncludePath( $comPath . '/models', 'ContentModel' );

abstract class ModArticlesAccordionHelper
{
	public static function getList(&$params)
	{
		// Get an instance of the generic articles model
		$articles = JModelLegacy::getInstance('Articles', 'ContentModel', array('ignore_request' => true));

		// Set application parameters in model
		$app       = JFactory::getApplication();
		$appParams = $app->getParams();
		$articles->setState('params', $appParams);

		// Set the filters based on the module params
		$articles->setState('list.start', 0);
		$articles->setState('list.limit', (int) $params->get('count', 0));
		$articles->setState('filter.published', 1);

		// Access filter
		$access     = !JComponentHelper::getParams('com_content')->get('show_noauth');
		$authorised = JAccess::getAuthorisedViewLevels(JFactory::getUser()->get('id'));
		$articles->setState('filter.access', $access);

		// Prep for Normal or Dynamic Modes
		// $mode = $params->get('mode', 'normal');
		$catids = $params->get('catid');
		$articles->setState('filter.category_id.include', (bool) $params->get('category_filtering_type', 1));

		// Category filter
		if ($catids)
		{
			// if ($params->get('show_child_category_articles', 0) && (int) $params->get('levels', 0) > 0)
			// {
			// 	// Get an instance of the generic categories model
			// 	$categories = JModelLegacy::getInstance('Categories', 'ContentModel', array('ignore_request' => true));
			// 	$categories->setState('params', $appParams);
			// 	$levels = $params->get('levels', 1) ? $params->get('levels', 1) : 9999;
			// 	$categories->setState('filter.get_children', $levels);
			// 	$categories->setState('filter.published', 1);
			// 	$categories->setState('filter.access', $access);
			// 	$additional_catids = array();

			// 	foreach ($catids as $catid)
			// 	{
			// 		$categories->setState('filter.parentId', $catid);
			// 		$recursive = true;
			// 		$items     = $categories->getItems($recursive);

			// 		if ($items)
			// 		{
			// 			foreach ($items as $category)
			// 			{
			// 				$condition = (($category->level - $categories->getParent()->level) <= $levels);

			// 				if ($condition)
			// 				{
			// 					$additional_catids[] = $category->id;
			// 				}
			// 			}
			// 		}
			// 	}

			// 	$catids = array_unique(array_merge($catids, $additional_catids));
			// }

			$articles->setState('filter.category_id', $catids);
		}

		// Ordering
		$ordering = $params->get('article_ordering', 'a.ordering');

		if (trim($ordering) == 'random')
		{
			$articles->setState('list.ordering', JFactory::getDbo()->getQuery(true)->Rand());
		}
		else
		{
			$articles->setState('list.ordering', $params->get('article_ordering', 'a.ordering'));
			$articles->setState('list.direction', $params->get('article_ordering_direction', 'ASC'));
		}

		// New Parameters
		$articles->setState('filter.featured', $params->get('show_front', 'show'));
		// $articles->setState('filter.author_id', $params->get('created_by', ""));
		// $articles->setState('filter.author_id.include', $params->get('author_filtering_type', 1));
		// $articles->setState('filter.author_alias', $params->get('created_by_alias', ""));
		// $articles->setState('filter.author_alias.include', $params->get('author_alias_filtering_type', 1));
		$excluded_articles = $params->get('excluded_articles', '');

		if ($excluded_articles)
		{
			$excluded_articles = explode("\r\n", $excluded_articles);
			$articles->setState('filter.article_id', $excluded_articles);

			// Exclude
			$articles->setState('filter.article_id.include', false);
		}

		$date_filtering = $params->get('date_filtering', 'off');

		if ($date_filtering !== 'off')
		{
			$articles->setState('filter.date_filtering', $date_filtering);
			$articles->setState('filter.date_field', $params->get('date_field', 'a.created'));
			$articles->setState('filter.start_date_range', $params->get('start_date_range', '1000-01-01 00:00:00'));
			$articles->setState('filter.end_date_range', $params->get('end_date_range', '9999-12-31 23:59:59'));
			$articles->setState('filter.relative_date', $params->get('relative_date', 30));
		}

		// Filter by language
		$articles->setState('filter.language', $app->getLanguageFilter());

		$items = $articles->getItems();

		// Display options
		// $show_date        = $params->get('show_date', 0);
		// $show_date_field  = $params->get('show_date_field', 'created');
		// $show_date_format = $params->get('show_date_format', 'Y-m-d H:i:s');
		$show_category    = $params->get('show_category', 0);
		// $show_hits        = $params->get('show_hits', 0);
		// $show_author      = $params->get('show_author', 0);
		$show_introtext   = $params->get('show_introtext', 0);
		$introtext_limit  = $params->get('introtext_limit', 1000);

		// Find current Article ID if on an article page
		$option = $app->input->get('option');
		$view   = $app->input->get('view');

		if ($option === 'com_content' && $view === 'article')
		{
			$active_article_id = $app->input->getInt('id');
		}
		else
		{
			$active_article_id = 0;
		}

		// Prepare data for display using display options
		foreach ($items as &$item)
		{
			$item->slug    = $item->id . ':' . $item->alias;
			$item->catslug = $item->catid . ':' . $item->category_alias;

			if ($access || in_array($item->access, $authorised))
			{
				// We know that user has the privilege to view the article
				$item->link = JRoute::_(ContentHelperRoute::getArticleRoute($item->slug, $item->catid, $item->language));
			}
			else
			{
				$menu      = $app->getMenu();
				$menuitems = $menu->getItems('link', 'index.php?option=com_users&view=login');

				if (isset($menuitems[0]))
				{
					$Itemid = $menuitems[0]->id;
				}
				elseif ($app->input->getInt('Itemid') > 0)
				{
					// Use Itemid from requesting page only if there is no existing menu
					$Itemid = $app->input->getInt('Itemid');
				}

				$item->link = JRoute::_('index.php?option=com_users&view=login&Itemid=' . $Itemid);
			}

			// Used for styling the active article
			$item->active      = $item->id == $active_article_id ? 'active' : '';
			$item->displayDate = '';

			// if ($show_date)
			// {
			// 	$item->displayDate = JHtml::_('date', $item->$show_date_field, $show_date_format);
			// }

			if ($item->catid)
			{
				$item->displayCategoryLink  = JRoute::_(ContentHelperRoute::getCategoryRoute($item->catid));
				$item->displayCategoryTitle = $show_category ? '<a href="' . $item->displayCategoryLink . '">' . $item->category_title . '</a>' : '';
			}
			else
			{
				$item->displayCategoryTitle = $show_category ? $item->category_title : '';
			}

			// $item->displayHits       = $show_hits ? $item->hits : '';
			// $item->displayAuthorName = $show_author ? $item->author : '';

			if ($show_introtext)
			{
				$item->introtext = JHtml::_('content.prepare', $item->introtext, '', 'mod_articles_category.content');
				$item->introtext = self::_cleanIntrotext($item->introtext);
			}

			$item->displayIntrotext = $show_introtext ? self::truncate($item->introtext, $introtext_limit) : '';
			// $item->displayReadmore  = $item->alternative_readmore;
		}

		return $items;
	}

	public static function _cleanIntrotext($introtext)
	{
		// $introtext = str_replace('<p>', ' ', $introtext);
		// $introtext = str_replace('</p>', ' ', $introtext);
		// $introtext = strip_tags($introtext, '<a><em><strong>');
		$introtext = trim($introtext);

		return $introtext;
	}

	public static function truncate($html, $maxLength = 0)
	{
		$baseLength = strlen($html);

		// First get the plain text string. This is the rendered text we want to end up with.
		$ptString = JHtml::_('string.truncate', $html, $maxLength, $noSplit = true, $allowHtml = false);

		for ($maxLength; $maxLength < $baseLength;)
		{
			// Now get the string if we allow html.
			$htmlString = JHtml::_('string.truncate', $html, $maxLength, $noSplit = true, $allowHtml = true);

			// Now get the plain text from the html string.
			$htmlStringToPtString = JHtml::_('string.truncate', $htmlString, $maxLength, $noSplit = true, $allowHtml = false);

			// If the new plain text string matches the original plain text string we are done.
			if ($ptString == $htmlStringToPtString)
			{
				return $htmlString;
			}

			// Get the number of html tag characters in the first $maxlength characters
			$diffLength = strlen($ptString) - strlen($htmlStringToPtString);

			// Set new $maxlength that adjusts for the html tags
			$maxLength += $diffLength;

			if ($baseLength <= $maxLength || $diffLength <= 0)
			{
				return $htmlString;
			}
		}

		return $html;
	}

	// public static function groupBy($list, $fieldName, $article_grouping_direction, $fieldNameToKeep = null)
	// {
	// 	$grouped = array();

	// 	if (!is_array($list))
	// 	{
	// 		if ($list == '')
	// 		{
	// 			return $grouped;
	// 		}

	// 		$list = array($list);
	// 	}

	// 	foreach ($list as $key => $item)
	// 	{
	// 		if (!isset($grouped[$item->$fieldName]))
	// 		{
	// 			$grouped[$item->$fieldName] = array();
	// 		}

	// 		if (is_null($fieldNameToKeep))
	// 		{
	// 			$grouped[$item->$fieldName][$key] = $item;
	// 		}
	// 		else
	// 		{
	// 			$grouped[$item->$fieldName][$key] = $item->$fieldNameToKeep;
	// 		}

	// 		unset($list[$key]);
	// 	}

	// 	$article_grouping_direction($grouped);

	// 	return $grouped;
	// }

	// public static function groupByDate($list, $type = 'year', $article_grouping_direction, $month_year_format = 'F Y')
	// {
	// 	$grouped = array();

	// 	if (!is_array($list))
	// 	{
	// 		if ($list == '')
	// 		{
	// 			return $grouped;
	// 		}

	// 		$list = array($list);
	// 	}

	// 	foreach ($list as $key => $item)
	// 	{
	// 		switch ($type)
	// 		{
	// 			case 'month_year' :
	// 				$month_year = JString::substr($item->created, 0, 7);

	// 				if (!isset($grouped[$month_year]))
	// 				{
	// 					$grouped[$month_year] = array();
	// 				}

	// 				$grouped[$month_year][$key] = $item;
	// 				break;

	// 			case 'year' :
	// 			default:
	// 				$year = JString::substr($item->created, 0, 4);

	// 				if (!isset($grouped[$year]))
	// 				{
	// 					$grouped[$year] = array();
	// 				}

	// 				$grouped[$year][$key] = $item;
	// 				break;
	// 		}

	// 		unset($list[$key]);
	// 	}

	// 	$article_grouping_direction($grouped);

	// 	if ($type === 'month_year')
	// 	{
	// 		foreach ($grouped as $group => $items)
	// 		{
	// 			$date                      = new JDate($group);
	// 			$formatted_group           = $date->format($month_year_format);
	// 			$grouped[$formatted_group] = $items;

	// 			unset($grouped[$group]);
	// 		}
	// 	}

	// 	return $grouped;
	// }
}
