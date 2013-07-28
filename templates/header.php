<html>

<head>

    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0" />
    <meta name="viewport" content="target-densitydpi=medium-dpi" />

    <link rel="stylesheet" type="text/css" href="http://yui.yahooapis.com/3.3.0/build/cssreset/reset-min.css">

    <link rel="stylesheet" href="<?php echo $jqui_styles ?>">
    <link rel="stylesheet" href="<?php echo $lb_styles ?>">
    <link href="<?php echo $stylesheet ?>" rel="stylesheet" type="text/css" />

    <script type="text/javascript" src="<?php echo $jquery ?>"></script>
    <script type="text/javascript" src="<?php echo $jqui ?>"></script>
    <script type="text/javascript" src="<?php echo $jqui_tp ?>"></script>
    <script type="text/javascript" src="<?php echo $jqui_cb ?>"></script>
    <script type="text/javascript" src="<?php echo $jqui_top ?>"></script>
    <script type="text/javascript" src="<?php echo $jq_edit ?>"></script>

    <script type="text/javascript" src="<?php echo $flot ?>"></script>
    <script type="text/javascript" src="<?php echo $flot_pie ?>"></script>
    <script type="text/javascript" src="<?php echo $flot_tt ?>"></script>
    <script type="text/javascript" src="<?php echo $flot_rl ?>"></script>
    <script type="text/javascript" src="<?php echo $flot_st ?>"></script>
    <script type="text/javascript" src="<?php echo $flot_sel ?>"></script>

    <script type="text/javascript" src="<?php echo $dt ?>"></script>
    <script type="text/javascript" src="<?php echo $lb ?>"></script>

    <script type="text/javascript" src="<?php echo $caman ?>"></script>

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




