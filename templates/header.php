<!DOCTYPE html>

<html>

<head>

    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0" />
    <meta name="viewport" content="initial-scale=1.0"/>
    <!--<meta name="viewport" content="target-densitydpi=medium-dpi" />-->

    <?php if (!$mobile): ?>
    <link rel="stylesheet" type="text/css" href="http://yui.yahooapis.com/3.3.0/build/cssreset/reset-min.css">

    <?php foreach($st as $s): ?>
    <link href="<?php echo $s ?>" type="text/css" rel="stylesheet" >
    <?php endforeach; ?>

    <link rel="stylesheet" id="google-webfonts-css"  href="http://fonts.googleapis.com/css?family=Maven+Pro%7CDroid+Serif%3A400italic%7CDroid+Sans%3A400%2C700&#038;ver=3.6.1" type="text/css" media="all" />

    <?php foreach($js as $j): ?>
    <script type="text/javascript" src="<?php echo $j ?>"></script>
    <?php endforeach; ?>

    <?php if ($sb): ?>
    <script type="text/javascript" src="<?php echo $template_url ?>js/pages/sidebar.js"></script>
    <?php endif; ?>

    <script type="text/javascript">
        $(function() {
            $('.debug').click(function() {
                $(this).next('pre').slideToggle()
            })
        })

    </script>
    <?php endif; ?>

    <?php echo $header ?>

    <title><?php echo $title ?></title>

</head>

<body>

<?php if (!$mobile): ?>
<div id="wrapper">
<?php endif; ?>

<?php if ($hf): ?>
<div id="header">
    <ul id="menu">
        <?php if ($this->staff): ?>
        <li class="selected"><a href="/robot">Robot Stats</a></li>
        <!--<li><a href="/vstat">Visit Stats</a></li>-->
        <li><a href="/fault">Fault Report</a></li>
        <?php endif; ?>
        <!--<li><a href="/dc">Data</a></li>-->
    </ul>

    <h1><a href="/">MX</a></h1>

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
<?php endif; ?>

<?php if ($sb): ?>
    <?php include($template_path.'sidebar.php'); ?>
<?php endif; ?>


<?php if (!$mobile): ?>
<div class="cont_wrap">
<div id="container">

    <div class="content">

<?php endif; ?>



