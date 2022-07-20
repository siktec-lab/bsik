'use strict';

export { SikSlider }
export default class SikSlider {

	s = null;
    
	static defaults = {
    	currentClass    : "current-slide",
    	initSlide       : 0,
        loopSlides      : true,
        autoSlide       : false,
        autoInterval    : 3000,
        autoPauseOver   : true,
        enableSwipe     : true,
        defaultAnimation : {
        	show 	    : "none",
            hide 	    : "none",
            duration    : 1000 
        },
        slides 	   : {},
        animations : {},
        swipes : {
            left    : function() { this.next(); },
            right   : function() { this.prev(); },
            up      : null,
            down    : null
        }
    };
    
	options = {};
    
    el = {
    	wrapper : null,
        slider  : null,
        slides  : null
    };
    slides          = [];
    currentIndex    = -1;
    _timer          = null;
    swipeRegion     = null;
    swipeElement    = null;
    wait            = false; //is a flag raised for waiting for animations to be done

    constructor(ele, opt) {
        
    	//Element or query?
    	if (typeof ele === "string") {
        	ele = document.querySelector(ele);
        }
        
        //Bind static:
    	this.s = SikSlider;
        
    	//Get slider parts:
    	if (!this._load(ele)) {
        	this.s._warn("Cant build the slider - aborting :(");	
        } else {
        
        	//Set options:
        	this.setOptions(opt);
            
            //Parse slides:
            this._loadSlides();
            //console.log(this.slides);
            
            //Set timer:
            this._timer = new this.s.Timer(
            	this.next.bind(this),
                this.options.autoInterval
            );
            
            //Load init slide:
            this.show(this.options.initSlide);
            
            //core handlers:
            this._attachHandlers();
            
        	//Save the instance in the element:
        	ele.SikSlider = this;
        }  
    }
    
    setOptions(opt) {
    	//Extend defaults:
        this.s._extend(
        	this.options, 
            this.s.defaults, 
            typeof opt === 'object' ? opt : {}
       );
       //Extend animations:
       this.s._extend(
       		this.animations,
            this.options.animations
       );
    }
   
    hide(slide = false, dir = 1) {
    	if (this.wait) return;
    	let index = slide !== false 
        			? this._getSlideIndex(slide) 
                    : this.currentIndex;
        
    	if (index >= 0 && index === this.currentIndex) {
        	this.slides[index].ele.style.zIndex  = 0;
            this.slides[index].ele.classList.remove(this.options.currentClass);
			this.currentIndex = -1;
            //anim
            return this._exec(index, this.slides[index].ele, false, dir);
        }
        return 0;
    }
    
    show(slide, dir = 1) {
    	if (this.wait) return;
    	let index = this._getSlideIndex(slide);
        //console.log(slide);
        if (index >= 0 && index < this.slides.length && index !== this.currentIndex) {
            //First hide current:
            let requestedTime = this.hide(false, dir);
            this.wait = true;
            window.setTimeout(() => {
            	this.slides[index].ele.style.zIndex  = 1;
                this.slides[index].ele.classList.add(this.options.currentClass);
                this.currentIndex = index;

                //anim
                let requestedTime = this._exec(index, this.slides[index].ele, true, dir);
				
                window.setTimeout(() => {
                	//auto slide if needed:
                    this.wait = false;
                    let after  = this._getSlideDefinition(index, "callback-after");
                    if (typeof after === "function") 
                    	after.call(this, this.slides[index].ele, true, dir);
                        
                    if (this.options.autoSlide && 
                        (this._timer.state === "running" || this._timer.state === "init")
                    ) {
                        this._timer.restart();
                    }
                }, requestedTime);
              
            }, requestedTime);
        }
    }
    _exec(index, ele, show, dir) {
    	let [anim, duration] = this._getSlideDefinition(index, show ? "show-anim" : "hide-anim");
        let before = this._getSlideDefinition(index, "callback-before");
        
        if (typeof before === "function") {
            before.call(this, ele, show, dir);
        }
        if (typeof anim === "function") {
            anim.call(this, ele, show, duration, dir);
            return duration + 5;
        } else {
            this.s._warn("Animation not found! for slide", index, ele);
        }
        return 0;
    }
    next() {
    	if (this.wait) return false;
    	let index = this.currentIndex + 1;
        if (index >= this.slides.length && this.options.loopSlides) {
        	index = 0;
        }
        this.show(index, 1);
    }
    
    prev() {
    	if (this.wait) return false;
    	let index = this.currentIndex - 1;
        if (index < 0 && this.options.loopSlides) {
        	index = this.slides.length - 1;
        }
        this.show(index, -1);
    }
    start() {
    	this.options.autoSlide = true;
    	return this._timer.restart();
    }
    stop() {
    	this.options.autoSlide = false;
    	return this._timer.stop();
    }
    autoState() {
    	return this._timer.state;
    }
    
    _load(ele) {
    	//Main wrapper:
    	this.el.wrapper = ele;
    	if (!this.el.wrapper) {
        	this.s._warn("invalid slider element given to constructor", this.el.wrapper);
            return false;
        }
        //Slides container:
        this.el.slider  = this.el.wrapper.querySelector("ul.sik-slides");
        if (!this.el.slider) {
        	this.s._warn("no slider list found in this element", this.el.slider);
            return false;
        }
		//Slide container:
        this.el.slides  = this.el.slider.querySelectorAll("ul.sik-slides > li");
        if (this.el.slides.length === 0) {
        	this.s._warn("no slides found in this element", this.el.slider);
            return false;
        }
        return true;
    }
    
    _loadSlides() {
    	let index = 0;
        this.slides = Array(this.el.slides);
    	for (let slide of this.el.slides) {
            //normalize:
            slide.style.zIndex  = 0;
            slide.style.opacity = 0;
            //slide object
            let obj = {
            	name  : slide.hasAttribute("slide-name") ? slide.getAttribute("slide-name") : "",
                index : index,
                ele   : slide
            }
            this.slides[index] = obj;
            index++;
        }
    }
    _getSlideIndex(slide) {
    	return typeof slide === 'string' ? this._nameToIndex(slide) : slide;
    }
    _nameToIndex(name) {
    	for (let slide of this.slides)
        	if (slide.name === name)
            	return slide.index;
        return -1;
    }
    _indexToName(index) {
    	for (let slide of this.slides)
        	if (slide.index === index)
            	return slide.name;
        return "";
    }
    _getSlideDefinition(index, type) {
    	let name = this._indexToName(index);
        if (this.options.slides.hasOwnProperty(name)) {
            switch (type) {
                case "show-anim":
					if (this.options.slides[name].hasOwnProperty("show")) 
                    	return [
                        	this.animations[this.options.slides[name].show],
                            this.options.slides[name].duration ?? this.options.defaultAnimation.duration
                        ];
                break;
                case "hide-anim": 
                	if (this.options.slides[name].hasOwnProperty("hide")) 
                    	return [
                        	this.animations[this.options.slides[name].hide],
                            this.options.slides[name].duration ?? this.options.defaultAnimation.duration
                        ];
                break;
                case "callback-after":
					if (this.options.slides[name].hasOwnProperty("after")) 
                    	return this.options.slides[name].after;
                break;
                case "callback-before": 
					if (this.options.slides[name].hasOwnProperty("before")) 
                    	return this.options.slides[name].before;
                break;
            }
        }
        //defaults:
        switch (type) {
            case "show-anim":
                return [
                	this.animations[this.options.defaultAnimation.show],
                    this.options.defaultAnimation.duration
                ]
            case "hide-anim":
                return [
                	this.animations[this.options.defaultAnimation.hide],
                    this.options.defaultAnimation.duration
                ]
        }
        return false;
    }
    _attachHandlers() {
    	// moves over the unordered list
        this.el.slider.addEventListener("mouseenter", () => {
        	if (this.options.autoSlide && this.options.autoPauseOver) {
            	this._timer.pause();
            }
        });
        this.el.slider.addEventListener("mouseleave", () => {
        	if (   this.options.autoSlide 
            	&& this.options.autoPauseOver 
                && this._timer.state === "pause"
            ) {
            	this._timer.resume();
            }
        });
        if (this.options.enableSwipe && window.ZingTouch) {
            this.swipeRegion = ZingTouch.Region(this.el.slider, false, false);
            var swipeGesture = new ZingTouch.Swipe();
            var panGesture = new ZingTouch.Pan({threshold: 10});
            this.swipeRegion.bind(this.el.slider, swipeGesture, this._updateGesture.bind(this, "swipe"));
            this.swipeRegion.bind(this.el.slider, panGesture, this._updateGesture.bind(this, "pan"));
            // this.el.slider.addEventListener('touchstart', this._updateGestureStart.bind(this), false);
            // this.el.slider.addEventListener('touchend', this._updateGestureEnd.bind(this), false); 
        } else if (this.options.enableSwipe) {
            this.s._warn("Can't enable touch support ZingTouch is required.");
        }
    }
    _updateGesture(who, event) {
        let angle = -1;
        if (who === "swipe")
            angle = event.detail.data.length ? event.detail.data[0].currentDirection : -1;
        else
            angle = event.detail.data.length ? event.detail.data[0].directionFromOrigin : -1;
        //Get direction:
        let dir = "";
        if (angle >=0 ) {
            if (angle <= 45 || angle >= 315) {
                dir = "right";
            } else if (angle >= 225) {
                dir = "down";
            } else if (angle >= 135) {
                dir = "left";
            } else if (angle >= 45) {
                dir = "up";
            }
            if (who === "swipe") {
                if (this.options.swipes.hasOwnProperty(dir) && typeof this.options.swipes[dir] === 'function') {
                    (event.detail.events).forEach(_e => _e.originalEvent.preventDefault());
                    this.options.swipes[dir].call(this);
                }
            } else {
                if (dir === "left" || dir === "right") {
                    (event.detail.events).forEach(_e => _e.originalEvent.preventDefault());
                }
            }
        }
        return;
    }
    static Timer = function(callback, _delay) {
  
        var timerId = null;
        var start = 0;
        var remaining = _delay;
        var delay = _delay;
        var cb = callback;
        this.state = "init"; // pause, running, stop
        
        this.pause = function() {
        	if (this.state === "running") {
                window.clearTimeout(timerId);
                timerId = null;
                remaining -= Date.now() - start;
                this.state = "pause";
                return true;
            }
            return false;
        };
        
        this.resume = function() {
            if (this.state === "running") 
            	return false;
            start = Date.now();
            this.state = "running";
            timerId = window.setTimeout(cb, remaining);
            return true;
        };
        
        this.stop = function() {
        	if (timerId !== null) {
            	window.clearTimeout(timerId);
            }
            remaining = delay;
            timerId = null;
            this.state = "stop";
            return true;
        };
        
        this.restart = function() {
        	this.stop();
            return this.resume();
        };
    };
    
    static _extend() {
        for(var i=1; i<arguments.length; i++)
            for(var key in arguments[i])
                if(arguments[i].hasOwnProperty(key))
                    arguments[0][key] = arguments[i][key];
        return arguments[0];
    }
    static _warn(message, ...args) {
        console.warn(`SikSlider Says: ${message}`, ...args);
    }
    animations = {
    	fade : (slide, show, duration, dir) => {
            let anim = slide.animate(
                [
                  { opacity: show ? 0 : 1 },
                  { opacity: show ? 1 : 0 }
                ],
                { duration: duration, easing: 'ease', fill: 'forwards' },  
            );
            anim.commitStyles();
        },
        slide : (slide, show, duration, dir) => {
        	let tran = dir * 100;
            let anim = slide.animate(
                [
                  { opacity: show ? 0 : 1, transform: show ? `translate(${tran}%, 0)` : `translate(0, 0)` },
                  { opacity: show ? 1 : 0, transform: show ? `translate(0, 0)` : `translate(${-tran}%, 0)` }
                ],
                { duration: duration, easing: 'ease', fill: 'forwards' },  
            );
            anim.commitStyles();
        },
        fadeUp : (slide, show, duration, dir) => {
            let anim = slide.animate(
                [
                  { opacity: 0, transform: "translate(0, 5%)" },
                  { opacity: 1, transform: "translate(0, 0)" }
                ],
                { duration: duration, easing: 'ease', fill: 'forwards' }, 
            );
            anim.commitStyles();
        },
        fadeDown : (slide, show, duration, dir) => {
            let anim = slide.animate(
                [
                  { opacity: 1, transform: "translate(0, 0)" },
                  { opacity: 0, transform: "translate(0, 5%)" }
                ],
                { duration: duration, easing: 'ease', fill: 'forwards' }, 
            );
            anim.commitStyles();
        },
        zoomIn : (slide, show, duration, dir) => {
            let anim = slide.animate(
                [
                  { opacity: 0, transform: "scale(0.8)" },
                  { opacity: 1, transform: "scale(1)" }
                ],
                { duration: duration, easing: 'ease', fill: 'forwards' }, 
            );
            anim.commitStyles();
        },
        zoomOut : (slide, show, duration, dir) => {
            let anim = slide.animate(
                [
                  { opacity: 1, transform: "scale(1)" },
                  { opacity: 0, transform: "scale(0.8)" }
                ],
                { duration: duration, easing: 'ease', fill: 'forwards' }, 
            );
            anim.commitStyles();
        },
        
    	none : (slide, show, duration, dir) => {
        	if (show) {
            	slide.style.opacity = 1;
            } else {
            	slide.style.opacity = 0;
            }
        }
    };
}
