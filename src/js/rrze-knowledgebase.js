const button = document.querySelector('h2#rrze-kb-toc-title');
const toc = document.querySelector('.rrze-kb-toc');

button.addEventListener('click', () => {
    toc.classList.toggle('open');
});

toc.querySelectorAll('a').forEach(link => {
    link.addEventListener('click', () => {
        toc.classList.remove('open');
    });
});