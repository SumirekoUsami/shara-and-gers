<?php declare(strict_types=1);
require "include/funs.php";
use Netcarver\Textile;

const defaultLang = "eng";
const perPage     = 15;

chdir("..");
$data = simplexml_load_file("metadata.xml");

$fList     = [];
$byAuthors = [];
$byTags    = [];

$authors = [];

// STEP 0. Prepare data
// STEP 0.1. Build lists
foreach($data->Story as $story) {
    $fList[] = $story;
}

$list = $fList;
usort($list, fn($a, $b) => strtotime((string) $b->Date) <=> strtotime((string) $a->Date));

foreach($list as $story) {
    $author = $story->Author;
    $slug   = (string) $author->attributes()->slug;
    $authors[$slug] = (string) $author;
    
    if(!isset($byAuthors[$slug]))
        $byAuthors[$slug] = [];
    
    $byAuthors[$slug][] = $story;
    
    foreach($story->attributes() as $tag) {
        $tag = (string) $tag;
        if(!isset($byTags[$tag]))
            $byTags[$tag] = [];
        
        $byTags[$tag][] = $story;
    }
}

// STEP 0.2. Paginate list
$list = array_chunk($list, perPage);


// STEP 1. Render
chdir("website");

// STEP 1.1 Render unfiltered index
for($i = 0; $i < sizeof($list); $i++) {
    render("Main", $i === 0 ? "index" : ("page" . ($i + 1)), [
        "paginator" => paginator($i + 1, perPage, sizeof($fList)),
        "stories"   => storiesArray($list[$i]),
    ]);
}

// STEP 1.2. Render per-tag indices
foreach($byTags as $tag => $stories) {
    if(!is_dir("dist/tags/$tag"))
        mkdir("dist/tags/$tag", 0755, true);
    
    $info  = @file_get_contents("info/tags/$tag.txt");
    $total = sizeof($stories);
    $pages = array_chunk($stories, perPage);
    for($i = 0; $i < sizeof($pages); $i++) {
        render("Tag", "tags/$tag/" . ($i === 0 ? "index" : ("page" . ($i + 1))), [
            "about"     => !$info ? "There is no information about this tag yet." : $info,
            "tagName"   => tagName($tag),
            "paginator" => paginator($i + 1, perPage, $total),
            "stories"   => storiesArray($pages[$i]),
        ]);
    }
}

// STEP 1.2. Render per-author indices
foreach($byAuthors as $author => $stories) {
    if(!is_dir("dist/~$author"))
        mkdir("dist/~$author", 0755, true);
    
    $info  = @file_get_contents("info/authors/$tag.txt");
    $total = sizeof($stories);
    $pages = array_chunk($stories, perPage);
    for($i = 0; $i < sizeof($pages); $i++) {
        render("Author", "~$author/" . ($i === 0 ? "index" : ("page" . ($i + 1))), [
            "about"      => !$info ? "There is no information about this writer yet." : $info,
            "authorName" => $authors[$author],
            "total"      => $total,
            "paginator"  => paginator($i + 1, perPage, $total),
            "stories"    => storiesArray($pages[$i]),
        ]);
    }
}

// STEP 1.3. Render stories
$wd = getcwd();
foreach($fList as $story) {
    chdir("../stories");
    chdir((string) $story->Author->attributes()->slug);
    chdir((string) $story->Title->attributes()->slug);
    
    $langs = [];
    foreach(glob("*.textile") as $text)
        $langs[] = substr($text, 0, 3);
    
    foreach($langs as $lang) {
        $output = "language=$lang/index";
        if($lang === defaultLang)
            $output = "index";
        
        $output = storyUrl($story) . "/$output";
        if(!is_dir(dirname("../../../website/dist$output")))
            mkdir(dirname("../../../website/dist$output"), 0755, true);
        
        $oStory = storiesArray([$story])[0];
        $oStory->text  = (new Textile\Parser)->parse(str_replace("�", "'", file_get_contents("$lang.textile")));
        $oStory->langs = $langs;
        
        render("Story", $output, [
            "story" => $oStory,
            "langs" => $langs,
        ]);
    }
    
    chdir($wd);
}

exit(0);
