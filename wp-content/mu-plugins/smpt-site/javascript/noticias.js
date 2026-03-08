/* Legacy script: kept for reference, intentionally not enqueued on the live site. */
$j = jQuery.noConflict();


    // Post Simples: Hide comments links with '0 Comentários'


$j(document).ready(function() {
    $j('a.comments-link').each(function() {
        if ($j(this).text().trim() === '0 Comentários') {
            $j(this).hide();
            const $jprevElement = $j(this).prev('i.far.fa-comments');
            if ($jprevElement.length) {
                $jprevElement.hide();
            }
        }
    });

	
	
    // Post Simples: Enhance blog entries
    $j('.blog-entry-inner').each(function() {
        const $jentry = $j(this);
        const $jcategoryDiv = $jentry.find('.blog-entry-category');
        const $jtitleH2 = $jentry.find('.blog-entry-title');
        const $jdateDiv = $jentry.find('.blog-entry-date');
        const $jsummaryDiv = $jentry.find('.blog-entry-summary');
        const $jthumbnailLink = $jentry.find('.thumbnail-link');
        const $jimg = $jentry.find('.thumbnail img');

        if ($jtitleH2.length) {
            // Create the flex container and add category and date
            const $jflexContainer = $j('<div>', { class: 'beforetitle' });
            if ($jcategoryDiv.length) $jflexContainer.append($jcategoryDiv);
            if ($jdateDiv.length) $jflexContainer.append($jdateDiv);
            $jtitleH2.before($jflexContainer);

            // Create and insert the "Ler notícia" link
            const $jlerNoticiaLink = $j('<a>', {
                href: $jentry.find('a').attr('href') || '#',
                text: 'Ler notícia completa →',
                class: 'ler-noticia-link'
            });
            if ($jsummaryDiv.length) $jsummaryDiv.after($jlerNoticiaLink);
        }

        // Insert the fadeBranco div before the img within the thumbnail
        if ($jthumbnailLink.length && $jimg.length) {
            const $jfadeBrancoDiv = $j('<div>', { class: 'fadeBranco' });
            $jthumbnailLink.prepend($jfadeBrancoDiv);
        }
    });

    // Post Simples: Responsive thumbnail positioning
    let hasRunOnce = false;

    function handleResize() {
        const isSmallScreen = window.matchMedia("(max-width: 767px)").matches;
        const $jarticles = $j('.blog-entry');
        if (isSmallScreen) {
            // Move thumbnail after title for small screens
            $jarticles.each(function() {
                const $jarticle = $j(this);
                const $jtitle = $jarticle.find('h2.blog-entry-title.entry-title');
                const $jthumbnail = $jarticle.find('div.thumbnail');
                if ($jtitle.length && $jthumbnail.length) {
                    $jtitle.after($jthumbnail);
                }
            });
            hasRunOnce = true;
        } else if (hasRunOnce) {
            // Move thumbnail before content for larger screens
            $jarticles.each(function() {
                const $jarticle = $j(this);
                const $jthumbnail = $jarticle.find('div.thumbnail');
                const $jcontent = $jarticle.find('div.blog-entry-content');
                if ($jthumbnail.length && $jcontent.length) {
                    $jcontent.before($jthumbnail);
                }
            });
            hasRunOnce = false; // Reset the flag
        }
    }

    handleResize();
    $j(window).on('resize', handleResize);

    // Arquivos: Remove all headers with class 'page-header'
    $j('header.page-header').remove();

    // Arquivos: Select the article where the changes should be made
    const $jarticle = $j('article[id^="post-"]');

    if ($jarticle.length) {
        // Select the post title (h1) and the meta list
        const $jpostTitle = $jarticle.find('h1.single-post-title.entry-title');
        const $jmetaList = $jarticle.find('ul.meta.ospm-default.clr');

        // Check if both elements exist
        if ($jpostTitle.length && $jmetaList.length) {
            // Move the meta list after the title
            $jpostTitle.after($jmetaList);
        }
    }

	
// Arquivos: Remove 'Ocultar' links and their adjacent separators

$j(document).ready(function() {
    // Function to remove 'Ocultar' links and their adjacent separators
    function removeOcultarLinks($elements) {
        $elements.each(function() {
            const $element = $j(this);

            // Get the HTML content of the parent element
            let htmlContent = $element.html();
            
            // Remove the 'Ocultar' link from the HTML content and handle trailing slashes
            htmlContent = htmlContent.replace(/<a href="[^"]*" rel="category tag">Ocultar<\/a>\s*(?:\/\s*|<span class="owp-sep">\/<\/span>\s*)/, '');
            
            // If the link was at the end of the content, also handle trailing slashes
            htmlContent = htmlContent.replace(/<a href="[^"]*" rel="category tag">Ocultar<\/a>\s*(?:\/\s*|<span class="owp-sep">\/<\/span>)$/, '');
            
            // Set the cleaned HTML content back to the element
            $element.html(htmlContent);
        });
    }

    // Apply the function to both div.blog-entry-category.clr and li.meta-cat
    removeOcultarLinks($j('div.blog-entry-category.clr'));
    removeOcultarLinks($j('li.meta-cat'));
});

});
