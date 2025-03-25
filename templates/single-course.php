<?php
get_header();

$location = get_post_meta(get_the_ID(), 'sted', true);
$startDate = get_post_meta(get_the_ID(), 'startdato', true);
?>

    <main class="course-single">
        <h1><?php the_title(); ?></h1>

        <?php if ($location): ?>
            <p><strong>Sted:</strong> <?= esc_html($location) ?></p>
        <?php endif; ?>

        <?php if ($startDate): ?>
            <p><strong>Startdato:</strong> <?= esc_html($startDate) ?></p>
        <?php endif; ?>

        <div class="course-content">
            <?php the_content(); ?>
        </div>

        <hr>

        <h2>Påmelding</h2>
        <form method="post" action="">
            <label for="cm_name">Navn</label>
            <input type="text" name="cm_name" id="cm_name" required>

            <label for="cm_email">E-post</label>
            <input type="email" name="cm_email" id="cm_email" required>

            <button type="submit">Meld meg på</button>
        </form>
    </main>

<?php get_footer(); ?>