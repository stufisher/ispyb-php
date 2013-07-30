<html>

<head>

    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0" />
    <meta name="viewport" content="target-densitydpi=medium-dpi" />

    <link rel="stylesheet" type="text/css" href="http://yui.yahooapis.com/3.3.0/build/cssreset/reset-min.css">

    <?php foreach($st as $s): ?>
    <link href="<?php echo $s ?>" type="text/css" rel="stylesheet" >
    <?php endforeach; ?>

    <?php foreach($js as $j): ?>
    <script type="text/javascript" src="<?php echo $j ?>"></script>
    <?php endforeach; ?>

    <script type="text/javascript">
        $(function() {
            $('.debug').click(function() {
                $(this).next('pre').slideToggle()
            })
        })

    </script>

    <?php echo $header ?>

    <title><?php echo $title ?></title>

</head>

<body>

<div id="wrapper">

<div id="header">
    <ul id="menu">
        <?php if ($this->staff): ?>
        <li class="selected"><a href="/robot">Robot Stats</a></li>
        <li><a href="/vstat">Visit Stats</a></li>
        <li><a href="/fault">Fault Report</a></li>
        <?php endif; ?>
        <li class="selected"><a href="/dc">Data</a></li>
    </ul>

    <h1><a href="/">MXW</a></h1>
    <!--<h2>Hi <?php echo phpCAS::getUser(); ?></h2>-->

    <ul id="navigation">
        <?php foreach ($nav['p'] as $i => $p): ?>
            <?php if ($nav['l'][$i]): ?>
            <li><a href="<?php echo $nav['l'][$i] ?>"><?php echo $p ?></a></li>
            <?php else: ?>
            <li><?php echo $p ?></li>
            <?php endif ?>
        <?php endforeach ?>
    </ul>
</div>

<div id="container">

    <div class="content">




