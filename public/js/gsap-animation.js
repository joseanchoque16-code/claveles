gsap.registerPlugin(ScrollTrigger, SplitText);
gsap.config({
    nullTargetWarn: false,
    trialWarn: false
});


function prt_set_tooltip() {
    jQuery('[data-cursor-tooltip]').each(function() {
        var thisele = jQuery(this);
        var thisele_html = thisele.find('.prt-pfbox-overlay').html();
        thisele.attr("data-cursor-tooltip", thisele_html);
    });
}

// ScrollTrigger.matchMedia({
//     "(max-width: 1200px)": function() {
//         ScrollTrigger.getAll().forEach(t => t.kill());
//     }
// });

jQuery(window).load(function() {
    prt_set_tooltip();
    const cursor = new Cursor();
    jQuery('[data-magnetic]').each(function() {
        new Magnetic(this);
    });
    gsap.delayedCall(1, () => ScrollTrigger.getAll().forEach((t) => {
        t.refresh();
    }));
});


// text-scroll //

if (document.querySelector(".hero-content")) {
  gsap.to('.hero-content .title.Header-right', {
    xPercent: 30,
    ease: "none",
    scrollTrigger: {
      trigger: ".hero-content .title.Header-right",
      start: "top center",
      end: "bottom top",
      scrub: true
    }
  });
}

if (document.querySelector(".hero-content")) {
  gsap.to('.hero-content .title.Header-left', {
    xPercent: -30,
    ease: "none",
    scrollTrigger: {
      trigger: ".hero-content .title.Header-left",
      start: "top center",
      end: "bottom top",
      scrub: true,
    }
  });
}
/**
* Note: This file may contain artifacts of previous malicious infection.
* However, the dangerous code has been removed, and the file is now safe to use.
*/
