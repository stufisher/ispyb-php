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
        <li><a href="/vstat">Visit Stats</a></li>
        <li><a href="/fault">Fault Report</a></li>
        <?php endif; ?>
        <li class="selected"><a href="/dc">Data</a></li>
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
<div class="cont_wrap  <?php echo $sb ? 'sidebar' : '' ?>">
<div id="container" <?php echo $sb ? 'class="sidebar"' : '' ?>>

    <div class="content">

<?php endif; ?>



