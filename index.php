<?php
// Chooser only. v1 and v2 are separate apps that share a database after setup.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Book &amp; Board</title>
    <link rel="icon" href="v2/assets/images/logo.svg" type="image/svg+xml">
    <link rel="stylesheet" href="v2/assets/css/styles.css">
    <style>
        html,
        body {
            min-height: 100%;
        }

        body {
            display: flex;
            align-items: center;
        }

        #main.chooser {
            width: 100%;
            max-width: 52rem;
            padding-top: 2.5rem;
            padding-bottom: 2.5rem;
            text-align: center;
        }

        .chooser .page-intro {
            margin-bottom: 1.6rem;
        }

        .chooser .lede {
            margin-left: auto;
            margin-right: auto;
        }

        .chooser-grid {
            display: grid;
            gap: 1.15rem;
        }

        .chooser-panel {
            display: flex;
            flex-direction: column;
            justify-content: center;
            min-height: 12rem;
            padding: 2rem 1.6rem;
            background: var(--paper);
            border: 1px solid var(--line);
            border-radius: 22px;
            box-shadow: var(--shadow);
            color: inherit;
            text-decoration: none;
        }

        .chooser-panel:hover {
            color: inherit;
            text-decoration: none;
            border-color: var(--copper);
            transform: translateY(-2px);
            box-shadow: 0 18px 40px rgba(28, 25, 22, 0.1);
        }

        .chooser-panel h2 {
            margin: 0 0 0.4rem;
            font-family: var(--font-display);
            font-size: 2rem;
            font-weight: 500;
            color: var(--navy);
        }

        .chooser-panel p {
            margin: 0;
            color: var(--muted);
        }

        @media (min-width: 768px) {
            .chooser-grid {
                grid-template-columns: 1fr 1fr;
            }

            .chooser-panel {
                min-height: 16rem;
            }
        }
    </style>
</head>
<body>
    <main id="main" class="chooser">
        <div class="page-intro">
            <h1>Book &amp; Board</h1>
            <p class="lede">Choose a version.</p>
        </div>
        <div class="chooser-grid">
            <a class="chooser-panel" href="v1/">
                <h2>Version 1</h2>
                <p>Prototype</p>
            </a>
            <a class="chooser-panel" href="v2/">
                <h2>Version 2</h2>
                <p>Accounts and search</p>
            </a>
        </div>
    </main>
</body>
</html>
