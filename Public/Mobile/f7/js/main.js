;(function(){
    function handleLazyScroll() {
        var st = $(window).scrollTop();
        $('img.lazy').each(function(){
            var img = $(this);
            if (img.attr('src')) return;
            if (img.offset().top < $(window).height() + st) {
                img.attr('src', img.data('src'));
            }
        });
    }
    if ($('img.lazy').length > 0) {
        $(window).scroll(function(){
            handleLazyScroll();
        });
    }
        

    if (window.hljs) {
        hljs.configure({tabReplace: '    '});
        hljs.initHighlightingOnLoad();
    }

    if ($('.docs-content').length > 0) {
        $('.docs-content').find('h2, h3').each(function () {
            var h = $(this);
            if (!h.attr('id')) {
                var id = h.text().trim()
                .replace(/\ /g, '-')
                .replace(/\//g, '-')
                .replace(/"/g, '')
                .replace(/'/g, '-')
                .replace(/:/g, '')
                .toLowerCase().replace(/\-&-/g,'-');
                h.attr('id', id);
            }
            if (h.attr('id')) {
                h.click(function(){
                    document.location.hash = h.attr('id');
                    // var url = document.location.origin + document.location.pathname + '#' + h.attr('id');
                    // prompt('Copy url to clipboard', url);
                });
            }
        });
    }

    if ($('.docs-nav').length > 0) {
        var loc = document.location.href;
        if (loc.indexOf('?') >= 0) loc = loc.split('?')[0];
        if (loc.indexOf('/') >= 0) {
            loc = loc.split('/');
            loc = loc[loc.length - 1];
        }

        $('.docs-nav a').each(function () {
            var link = $(this).attr('href');
            if (loc == link && link != '#') $(this).addClass('active');
        });
    }
    // Docs scroll spy
    var demoDevicePreviewLink;
    function handleDeviceScroll() {
        var st = $(window).scrollTop();
        var firstPreviewPosition = $('[data-device-preview]:first').offset().top;
        var device = $('.docs-demo-device:not(.docs-inline-device)');
        var deviceStartOffset = device.parent().offset().top;
        var devicePosition = st - deviceStartOffset;
        if(devicePosition < firstPreviewPosition - deviceStartOffset) {
            devicePosition = firstPreviewPosition -  deviceStartOffset;
        }
        if (devicePosition + device.outerHeight() > device.parent().outerHeight()) {
            devicePosition = device.parent().outerHeight() - device.outerHeight();
        }
        if ($('.stop-scroll-device').length > 0) {
            var stopPosition = $('.stop-scroll-device').offset().top;
            if (devicePosition + device.outerHeight() > stopPosition - deviceStartOffset) {
                devicePosition = stopPosition - device.outerHeight() - deviceStartOffset;
            }
        }
        device.css({top: devicePosition});
        var newPreviewLink;
        $('[data-device-preview]').each(function(){
            var link = $(this);
            if (link.offset().top < st + $(window).height()/2 - 200) {
                newPreviewLink = link.attr('data-device-preview');
            }
        });
        if (!newPreviewLink) newPreviewLink = $('[data-device-preview]:first').attr('data-device-preview');
        if (newPreviewLink !== demoDevicePreviewLink) {
            demoDevicePreviewLink = newPreviewLink;
            device.find('.fade-overlay').addClass('visible');
            device.find('iframe')[0].onload = function() {
                setTimeout(function () {
                    device.find('.fade-overlay').removeClass('visible');
                }, 100);    
            };
            device.find('iframe').attr('src', newPreviewLink);
        }
    }
    if ($('.docs-content.with-device').length > 0) {
        $(window).resize(function(){
            handleDeviceScroll();
        });
        $(window).scroll(function(){
            handleDeviceScroll();
        });
        $(window).trigger('resize');
    }

    $('.app-show-shots a').click(function(e) {
        e.preventDefault();
        $(this).parent().hide().parents('.app').find('.app-shots').show();
    });

    $('.app-launcher span').click(function () {
        var iframe = $(this).parents('.device').find('iframe');
        iframe.attr('src', iframe.data('src'));
        $(this).parents('.app-launcher').remove();
    });
})();
