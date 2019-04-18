<?php
declare(strict_types=1);
namespace Ixocreate\Cms\Package;

use Ixocreate\Cms\Package\Config\Configurator;
use Ixocreate\Cms\Package\Router\Replacement\LangReplacement;
use Ixocreate\Cms\Package\Router\Replacement\ParentReplacement;
use Ixocreate\Cms\Package\Router\Replacement\RegionReplacement;
use Ixocreate\Cms\Package\Router\Replacement\SlugReplacement;
use Ixocreate\Cms\Package\Router\Replacement\UriReplacement;
use Ixocreate\Cms\Package\Site\Tree\Search\ActiveSearch;
use Ixocreate\Cms\Package\Site\Tree\Search\CallableSearch;
use Ixocreate\Cms\Package\Site\Tree\Search\HandleSearch;
use Ixocreate\Cms\Package\Site\Tree\Search\MaxLevelSearch;
use Ixocreate\Cms\Package\Site\Tree\Search\MinLevelSearch;
use Ixocreate\Cms\Package\Site\Tree\Search\NavigationSearch;
use Ixocreate\Cms\Package\Site\Tree\Search\OnlineSearch;

/** @var Configurator $cms */
$cms->addTreeSearchable(ActiveSearch::class);
$cms->addTreeSearchable(CallableSearch::class);
$cms->addTreeSearchable(HandleSearch::class);
$cms->addTreeSearchable(MaxLevelSearch::class);
$cms->addTreeSearchable(MinLevelSearch::class);
$cms->addTreeSearchable(NavigationSearch::class);
$cms->addTreeSearchable(OnlineSearch::class);

$cms->addRoutingReplacement(LangReplacement::class);
$cms->addRoutingReplacement(RegionReplacement::class);
$cms->addRoutingReplacement(ParentReplacement::class);
$cms->addRoutingReplacement(SlugReplacement::class);
$cms->addRoutingReplacement(UriReplacement::class);
