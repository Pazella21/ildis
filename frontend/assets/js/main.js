/**
* Template Name: SoftLand - v4.10.0
* Template URL: https://bootstrapmade.com/softland-bootstrap-app-landing-page-template/
* Author: BootstrapMade.com
* License: https://bootstrapmade.com/license/
*/
(function() {
  "use strict";

  /**
   * Easy selector helper function
   */
  const select = (el, all = false) => {
    if (typeof el !== 'string') {
      return el
    }

    const selector = el.trim()
    if (all) {
      return [...document.querySelectorAll(selector)]
    }

    return document.querySelector(selector)
  }

  /**
   * Easy event listener function
   */
  const on = (type, el, listener, all = false) => {
    if (el instanceof EventTarget) {
      el.addEventListener(type, listener)
      return
    }

    const selectEl = select(el, all)
    if (!selectEl) {
      return
    }

    if (all) {
      selectEl.forEach((node) => node.addEventListener(type, listener))
      return
    }

    selectEl.addEventListener(type, listener)
  }

  /**
   * Easy on scroll event listener 
   */
  const onscroll = (el, listener) => {
    el.addEventListener('scroll', listener)
  }

  /**
   * Toggle .header-scrolled class to #header when page is scrolled
   */
  let selectHeader = select('#header')
  if (selectHeader) {
    const headerScrolled = () => {
      if (window.scrollY > 100) {
        selectHeader.classList.add('header-scrolled')
      } else {
        selectHeader.classList.remove('header-scrolled')
      }
    }
    window.addEventListener('load', headerScrolled)
    onscroll(document, headerScrolled)
  }

  /**
   * Mobile nav toggle
   */
  const isMobileNavOpen = () => document.body.classList.contains('mobile-nav-active')

  const setMobileNavState = (open) => {
    const header = select('#header')
    const mobileNav = select('#mobile-nav')
    const drawer = select('.mobile-nav-drawer')

    document.body.classList.toggle('mobile-nav-active', open)
    if (header) {
      header.classList.toggle('mobile-nav-open', open)
    }
    if (mobileNav) {
      mobileNav.setAttribute('aria-hidden', open ? 'false' : 'true')
    }
    if (drawer) {
      drawer.setAttribute('aria-modal', open ? 'true' : 'false')
    }

    if (!open && mobileNav) {
      mobileNav.querySelectorAll('.dropdown-active').forEach((submenu) => {
        submenu.classList.remove('dropdown-active')
      })
      mobileNav.querySelectorAll('.dropdown-open').forEach((item) => {
        item.classList.remove('dropdown-open')
      })
    }
  }

  const toggleMobileNav = () => {
    setMobileNavState(!isMobileNavOpen())
  }

  on('click', '.mobile-nav-toggle', function(e) {
    e.preventDefault()
    toggleMobileNav()
  })

  on('click', '.mobile-nav-close, .mobile-nav-backdrop', function(e) {
    e.preventDefault()
    setMobileNavState(false)
  }, true)

  /**
   * Back to top button
   */
  let backtotop = select('.back-to-top')
  if (backtotop) {
    const toggleBacktotop = () => {
      if (window.scrollY > 100) {
        backtotop.classList.add('active')
      } else {
        backtotop.classList.remove('active')
      }
    }
    window.addEventListener('load', toggleBacktotop)
    onscroll(document, toggleBacktotop)
    on('click', '.back-to-top', function(e) {
      e.preventDefault()
      window.scrollTo({ top: 0, behavior: 'smooth' })
    })
  }

  /**
   * Mobile nav dropdowns activate
   */
  on('click', '#mobile-nav .dropdown > a', function(e) {
    if (!isMobileNavOpen()) {
      return
    }

    const parentLink = this.classList.contains('mobile-menu-link--parent')
    const submenu = this.nextElementSibling

    if (!parentLink || !submenu || submenu.tagName !== 'UL') {
      setMobileNavState(false)
      return
    }

    e.preventDefault()
    const parentItem = this.parentElement
    const isOpen = submenu.classList.toggle('dropdown-active')
    parentItem.classList.toggle('dropdown-open', isOpen)
  }, true)

  on('keydown', document, function(e) {
    if (e.key === 'Escape' && isMobileNavOpen()) {
      setMobileNavState(false)
    }
  })

  /**
   * Testimonials slider
   */
  const testimonialsSlider = select('.testimonials-slider')
  if (testimonialsSlider) {
    new Swiper('.testimonials-slider', {
      speed: 600,
      loop: true,
      autoplay: {
        delay: 5000,
        disableOnInteraction: false
      },
      slidesPerView: 'auto',
      pagination: {
        el: '.swiper-pagination',
        type: 'bullets',
        clickable: true
      }
    })
  }

  /**
   * Animation on scroll
   */
  window.addEventListener('load', () => {
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches
    if (!prefersReducedMotion) {
      AOS.init({
        duration: 1000,
        easing: 'ease-in-out',
        once: true,
        mirror: false
      })
    }
  })
      duration: 1000,
      easing: 'ease-in-out',
      once: true,
      mirror: false
    })
  });

})()
