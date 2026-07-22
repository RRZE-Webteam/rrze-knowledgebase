const button = document.querySelector('h2#rrze-kb-toc-title');
const toc = document.querySelector('.rrze-kb-toc');
console.log(button);
if (button) {
    button.addEventListener('click', () => {
        toc.classList.toggle('open');
    });
}

if (toc) {
    toc.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', () => {
            toc.classList.remove('open');
        });
    });
}

jQuery(document).ready(function($) {
    $('.rrze-kb-search-form .checklist-toggle').bind('mousedown', function(event) {
        event.preventDefault();
        let $checklist = $(this).parent();
        toggleChecklist($checklist);
    });

    // Keyboard navigation for accordions
    $('.rrze-kb-search-form .checklist-toggle').keydown(function(event) {
        if (event.keyCode == 32 || event.keyCode == 13) {
            event.preventDefault();
            let $checklist = $(this).parent();
            toggleChecklist($checklist);
        }
    });

    function toggleChecklist($checklist) {
        $($checklist).children('.checklist-toggle').toggleClass('active');
        $($checklist).children('.checklist').slideToggle();
        $($checklist).children().find('.dashicons.dashicons-arrow-down-alt2').toggleClass('dashicons-arrow-up-alt2');
    }
});