<?php
//不许删，删了把你删了！
//纯手搓的转换算法，原理非常简单，相信CZW看0.08眼就懂了


function djeh($json) {
    //var_dump($json.'万恶分隔符');
    if (!$json) { return "Empty..." ; }
    $data = json_decode($json,true);
    if (!$data) { return $json ; }
    $blocks = $data['blocks'];
    $entityMap = $data['entityMap'];

    $html = '';
    
    foreach ($blocks as $block) {
        $type = $block['type'];
        $text = $block['text'];
        $inlineStyleRanges = $block['inlineStyleRanges'];
        $entityRanges = $block['entityRanges'];

        // 根据 block 类型确定 HTML 标签
        switch ($type) {
            case 'unstyled':
                $tag = 'p';
                break;
            case 'header-one':
                $tag = 'h1';
                break;
            case 'header-two':
                $tag = 'h2';
                break;
            case 'header-three':
                $tag = 'h3';
                break;
            case 'atomic':
                $tag = 'div';
                break;
            default:
                $tag = 'p';
                break;
        }
        
        //获取数组中所有indices
        $indices=[0];
        $texts = [];
        $styles = [];
        $urls = [];
        $image = "";
        $total = array_merge($inlineStyleRanges, $entityRanges);
        foreach ($total as $range) {
            $entityKey = $range['key'];
            $entity = $entityMap[$entityKey];
            if ($entity['type'] == 'IMAGE') {
                $src = "/response.php?urlf=".$entity['data']['src'];
                $width = $entity['data']['width'];
                $height = $entity['data']['height'];
                $image .= '<img loading="lazy" src="' . $src. '" width="' . htmlspecialchars($width) . '" height="' . htmlspecialchars($height) . '">';
                $html .= "$image";
                continue;
            }
            $start = $range['offset'];
            $end = $start + $range['length'];
            array_push($indices,$start,$end);
            $indices = array_unique($indices);
            sort($indices);
        }
        
        //Continue if no styles provided
        if ($indices == [0]){
            $html .= "<$tag>$text</$tag>\n";
            continue;
        }
        if ($image){
            continue;
        }
        
        for ($i=0;$i<count($indices);$i++){
            if ($i<count($indices)-1){
                $texts[] = htmlspecialchars(mb_substr($text, $indices[$i], $indices[$i+1] - $indices[$i], 'UTF-8'));
            } else {
                $temp_str = htmlspecialchars(mb_substr($text, $indices[$i], null, 'UTF-8'));
                if ($temp_str){
                    $texts[] = $temp_str;
                }
                unset($temp_str);
            }
            
        }
        
        
        foreach ($inlineStyleRanges as $range) {
            $start = $range['offset'];
            $end = $start + $range['length'];
            $style = $range['style'];
            $s_key = array_search($start,$indices);
            $e_key = array_search($end,$indices);
            for ($i=$s_key;$i < $e_key;$i++){
                $styles[$i][] = $style;
            }
        }
        //var_dump($texts,$styles);
        foreach ($entityRanges as $range) {
            $start = $range['offset'];
            $end = $start + $range['length'];
            $entityKey = $range['key'];
            $entity = $entityMap[$entityKey];
            $s_key = array_search($start,$indices);
            $e_key = array_search($end,$indices);
            for ($i=$s_key;$i < $e_key;$i++){
                $urls[$i] = $entity['data']['url'];
            }
        }
        // 处理 inline styles
        $styledText = '';
        for ($i=0;$i<=count($texts);$i++){
            $text = $texts[$i];

            $style = $styles[$i] ? implode(' ', $styles[$i]) : "";
            
            //echo $style;            
            $url = $urls[$i];
            
            $styledText .= '<span class="' . $style. '">';
            
            if ($url){ 
                $styledText .= '<a href="'.$url.'" rel="noreferrer" target="_blank" class="text-sky-600">';
            } 
            
            $styledText .= $text;
            
            if ($url){ 
                $styledText .= '</a>';
            } 
            $styledText .= '</span>';
            
        }
        if ($block['data']){
            $html .= "<div class='".implode(' ',$block['data'])."'>";
            
        } else{
            $html .= "<div>";
        }
        

        $html .= "<$tag>$styledText</$tag></div>\n";
        
        
    }
    
    return $html;
}

//想测试啥放这
//$test = '';
//echo djeh($test);
?>

