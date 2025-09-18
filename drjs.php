<?php
function htmlToDraftJs($html) {
    /*// 解析HTML
    $dom = new DOMDocument();
    @$dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    
    // 初始化转换结果
    $blocks = [];
    $entityMap = [];

    // 遍历DOM节点
    $nodes = $dom->getElementsByTagName('*');
    foreach ($nodes as $node) {
        if ($node->nodeName === '#text') {
            continue; // 忽略文本节点
        }

        // 处理不同类型的标签
        switch (true) {
            case in_array($node->nodeName, ['H1', 'H2', 'H3', 'H4', 'H5', 'H6']):
                $type = 'header-' . substr($node->nodeName, 1);
                break;
            case $node->nodeName === 'STRONG':
                $type = 'unstyled';
                $inlineStyle = ['BOLD'];
                break;
            case $node->nodeName === 'EM':
                $type = 'unstyled';
                $inlineStyle = ['ITALIC'];
                break;
            case $node->nodeName === 'U':
                $type = 'unstyled';
                $inlineStyle = ['UNDERLINE'];
                break;
            case $node->nodeName === 'S':
                $type = 'unstyled';
                $inlineStyle = ['STRIKETHROUGH'];
                break;
            case $node->nodeName === 'A':
                $type = 'unstyled';
                $entityKey = count($entityMap);
                $entityMap[$entityKey] = [
                    'type' => 'LINK',
                    'mutability' => 'MUTABLE',
                    'data' => [
                        'url' => $node->getAttribute('href'),
                    ],
                ];
                break;
            case $node->nodeName === 'IMG':
                $type = 'atomic';
                $entityKey = count($entityMap);
                $entityMap[$entityKey] = [
                    'type' => 'IMAGE',
                    'mutability' => 'IMMUTABLE',
                    'data' => [
                        'src' => $node->getAttribute('src'),
                    ],
                ];
                break;
            case $node->nodeName === 'BLOCKQUOTE':
                $type = 'blockquote';
                break;
            case $node->nodeName === 'CODE':
                $type = 'code-block';
                break;
            case in_array($node->nodeName, ['UL', 'OL']):
                $type = 'unordered-list-item';
                if ($node->nodeName === 'OL') {
                    $type = 'ordered-list-item';
                }
                break;
            default:
                $type = 'unstyled';
                break;
        }

        // 构建block
        $block = [
            'key' => uniqid(),
            'text' => $node->nodeValue,
            'type' => $type,
            'depth' => 0,
            'inlineStyleRanges' => [],
            'entityRanges' => [],
            'data' => [],
        ];

        // 添加内联样式
        if (isset($inlineStyle)) {
            $block['inlineStyleRanges'][] = [
                'offset' => 0,
                'length' => mb_strlen($node->nodeValue),
                'style' => $inlineStyle[0],
            ];
        }

        // 添加实体
        if (isset($entityKey)) {
            $block['entityRanges'][] = [
                'offset' => 0,
                'length' => mb_strlen($node->nodeValue),
                'key' => $entityKey,
            ];
        }

        // 添加block
        $blocks[] = $block;
    }

    // 返回转换后的数据
    return json_encode([
        'entityMap' => $entityMap,
        'blocks' => $blocks,
    ]);*/
  /*  $input = explode("\n",$html);
    $ret = '';
    foreach ($input as $one){
    $ret .= '{"blocks":[{"key":"'. uniqid().'","text":"'.$one.'","type":"unstyled","depth":0,"inlineStyleRanges":[],"entityRanges":[],"data":{}}],"entityMap":{}}';}
    return $ret;*/
    return $html;
}
function draftJsToHtml($json) {
    $data = json_decode($json, true);

    $html = '';
    foreach ($data['blocks'] as $block) {
        $text = htmlspecialchars($block['text']);
        $styles = [];
        $entities = [];

        // 处理内联样式
        foreach ($block['inlineStyleRanges'] as $range) {
            $styles[$range['offset']][] = $range['style'];
        }

        // 处理实体
        foreach ($block['entityRanges'] as $range) {
            $entities[$range['offset']][] = $range['key'];
        }

        // 构建HTML
        $currentOffset = 0;
        $lastStyle = null;
        $lastEntity = null;
        for ($i = 0; $i < mb_strlen($text); $i++) {
            $currentStyle = isset($styles[$i]) ? $styles[$i] : [];
            $currentEntity = isset($entities[$i]) ? $entities[$i][0] : null;

            if ($currentStyle !== $lastStyle || $currentEntity !== $lastEntity) {
                if ($currentOffset < $i) {
                    $html .= mb_substr($text, $currentOffset, $i - $currentOffset);
                }

                if (!empty($currentStyle)) {
                    $html .= '<span style="' . implode('; ', array_map(function($s) {
                        switch ($s) {
                            case 'BOLD': return 'font-weight: bold';
                            case 'ITALIC': return 'font-style: italic';
                            case 'UNDERLINE': return 'text-decoration: underline';
                            case 'STRIKETHROUGH': return 'text-decoration: line-through';
                        }
                    }, $currentStyle)) . '">';
                }

                if ($currentEntity !== null) {
                    $entityData = $data['entityMap'][$currentEntity];
                    if ($entityData['type'] === 'LINK') {
                        $html .= '<a href="' . htmlspecialchars($entityData['data']['url']) . '">';
                    } elseif ($entityData['type'] === 'IMAGE') {
                        $html .= '<img src="' . htmlspecialchars($entityData['data']['src']) . '" alt="" />';
                        continue; // 图像不需要包裹在其他标签中
                    }
                }

                $currentOffset = $i;
                $lastStyle = $currentStyle;
                $lastEntity = $currentEntity;
            }
        }

        if ($currentOffset < mb_strlen($text)) {
            $html .= mb_substr($text, $currentOffset);
        }

        if (!empty($lastStyle)) {
            $html .= '</span>';
        }

        if ($lastEntity !== null) {
            $entityData = $data['entityMap'][$lastEntity];
            if ($entityData['type'] === 'LINK') {
                $html .= '</a>';
            }
        }

        // 根据block类型添加包装标签
        switch ($block['type']) {
            case 'header-1': $html = "<h1>$html</h1>"; break;
            case 'header-2': $html = "<h2>$html</h2>"; break;
            case 'header-3': $html = "<h3>$html</h3>"; break;
            case 'header-4': $html = "<h4>$html</h4>"; break;
            case 'header-5': $html = "<h5>$html</h5>"; break;
            case 'header-6': $html = "<h6>$html</h6>"; break;
            case 'blockquote': $html = "<blockquote>$html</blockquote>"; break;
            case 'code-block': $html = "<pre><code>$html</code></pre>"; break;
            case 'unordered-list-item': $html = "<li>$html</li>"; break;
            case 'ordered-list-item': $html = "<li>$html</li>"; break;
            default: $html = "<p>$html</p>"; break;
        }

        
    }
    // 输出HTML
        return $html;
}

//echo htmlToDraftJs("damn");
?>