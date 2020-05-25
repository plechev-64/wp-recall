( function( a ) {
	if ( typeof define === "function" && define.amd ) {
		define( [ "jquery" ], a );
	} else {
		a( jQuery );
	}
} )( function( a ) {
	a.ui = a.ui || { };
	var b = a.ui.version = "1.12.1";
	( function() {
		var b, c = Math.max, d = Math.abs, e = /left|center|right/, f = /top|center|bottom/, g = /[\+\-]\d+(\.[\d]+)?%?/, h = /^\w+/, i = /%$/, j = a.fn.pos;
		function k( a, b, c ) {
			return [ parseFloat( a[0] ) * ( i.test( a[0] ) ? b / 100 : 1 ),
				parseFloat( a[1] ) * ( i.test( a[1] ) ? c / 100 : 1 ) ];
		}
		function l( b, c ) {
			return parseInt( a.css( b, c ), 10 ) || 0;
		}
		function m( b ) {
			var c = b[0];
			if ( c.nodeType === 9 ) {
				return {
					width: b.width(),
					height: b.height(),
					offset: {
						top: 0,
						left: 0
					}
				};
			}
			if ( a.isWindow( c ) ) {
				return {
					width: b.width(),
					height: b.height(),
					offset: {
						top: b.scrollTop(),
						left: b.scrollLeft()
					}
				};
			}
			if ( c.preventDefault ) {
				return {
					width: 0,
					height: 0,
					offset: {
						top: c.pageY,
						left: c.pageX
					}
				};
			}
			return {
				width: b.outerWidth(),
				height: b.outerHeight(),
				offset: b.offset()
			};
		}
		a.pos = {
			scrollbarWidth: function() {
				if ( b !== undefined ) {
					return b;
				}
				var c, d, e = a( "<div " + "style='display:block;position:absolute;width:50px;height:50px;overflow:hidden;'>" + "<div style='height:100px;width:auto;'></div></div>" ), f = e.children()[0];
				a( "body" ).append( e );
				c = f.offsetWidth;
				e.css( "overflow", "scroll" );
				d = f.offsetWidth;
				if ( c === d ) {
					d = e[0].clientWidth;
				}
				e.remove();
				return b = c - d;
			},
			getScrollInfo: function( b ) {
				var c = b.isWindow || b.isDocument ? "" : b.element.css( "overflow-x" ), d = b.isWindow || b.isDocument ? "" : b.element.css( "overflow-y" ), e = c === "scroll" || c === "auto" && b.width < b.element[0].scrollWidth, f = d === "scroll" || d === "auto" && b.height < b.element[0].scrollHeight;
				return {
					width: f ? a.pos.scrollbarWidth() : 0,
					height: e ? a.pos.scrollbarWidth() : 0
				};
			},
			getWithinInfo: function( b ) {
				var c = a( b || window ), d = a.isWindow( c[0] ), e = !!c[0] && c[0].nodeType === 9, f = !d && !e;
				return {
					element: c,
					isWindow: d,
					isDocument: e,
					offset: f ? a( b ).offset() : {
						left: 0,
						top: 0
					},
					scrollLeft: c.scrollLeft(),
					scrollTop: c.scrollTop(),
					width: c.outerWidth(),
					height: c.outerHeight()
				};
			}
		};
		a.fn.pos = function( b ) {
			if ( !b || !b.of ) {
				return j.apply( this, arguments );
			}
			b = a.extend( { }, b );
			var i, n, o, p, q, r, s = a( b.of ), t = a.pos.getWithinInfo( b.within ), u = a.pos.getScrollInfo( t ), v = ( b.collision || "flip" ).split( " " ), w = { };
			r = m( s );
			if ( s[0].preventDefault ) {
				b.at = "left top";
			}
			n = r.width;
			o = r.height;
			p = r.offset;
			q = a.extend( { }, p );
			a.each( [ "my", "at" ], function() {
				var a = ( b[this] || "" ).split( " " ), c, d;
				if ( a.length === 1 ) {
					a = e.test( a[0] ) ? a.concat( [ "center"
					] ) : f.test( a[0] ) ? [
						"center" ].concat( a ) : [ "center", "center" ];
				}
				a[0] = e.test( a[0] ) ? a[0] : "center";
				a[1] = f.test( a[1] ) ? a[1] : "center";
				c = g.exec( a[0] );
				d = g.exec( a[1] );
				w[this] = [ c ? c[0] : 0, d ? d[0] : 0 ];
				b[this] = [ h.exec( a[0] )[0], h.exec( a[1] )[0] ];
			} );
			if ( v.length === 1 ) {
				v[1] = v[0];
			}
			if ( b.at[0] === "right" ) {
				q.left += n;
			} else if ( b.at[0] === "center" ) {
				q.left += n / 2;
			}
			if ( b.at[1] === "bottom" ) {
				q.top += o;
			} else if ( b.at[1] === "center" ) {
				q.top += o / 2;
			}
			i = k( w.at, n, o );
			q.left += i[0];
			q.top += i[1];
			return this.each( function() {
				var e, f, g = a( this ), h = g.outerWidth(), j = g.outerHeight(), m = l( this, "marginLeft" ), r = l( this, "marginTop" ), x = h + m + l( this, "marginRight" ) + u.width, y = j + r + l( this, "marginBottom" ) + u.height, z = a.extend( { }, q ), A = k( w.my, g.outerWidth(), g.outerHeight() );
				if ( b.my[0] === "right" ) {
					z.left -= h;
				} else if ( b.my[0] === "center" ) {
					z.left -= h / 2;
				}
				if ( b.my[1] === "bottom" ) {
					z.top -= j;
				} else if ( b.my[1] === "center" ) {
					z.top -= j / 2;
				}
				z.left += A[0];
				z.top += A[1];
				e = {
					marginLeft: m,
					marginTop: r
				};
				a.each( [ "left", "top" ], function( c, d ) {
					if ( a.ui.pos[v[c]] ) {
						a.ui.pos[v[c]][d]( z, {
							targetWidth: n,
							targetHeight: o,
							elemWidth: h,
							elemHeight: j,
							collisionPosition: e,
							collisionWidth: x,
							collisionHeight: y,
							offset: [ i[0] + A[0], i[1] + A[1] ],
							my: b.my,
							at: b.at,
							within: t,
							elem: g
						} );
					}
				} );
				if ( b.using ) {
					f = function( a ) {
						var e = p.left - z.left, f = e + n - h, i = p.top - z.top, k = i + o - j, l = {
							target: {
								element: s,
								left: p.left,
								top: p.top,
								width: n,
								height: o
							},
							element: {
								element: g,
								left: z.left,
								top: z.top,
								width: h,
								height: j
							},
							horizontal: f < 0 ? "left" : e > 0 ? "right" : "center",
							vertical: k < 0 ? "top" : i > 0 ? "bottom" : "middle"
						};
						if ( n < h && d( e + f ) < n ) {
							l.horizontal = "center";
						}
						if ( o < j && d( i + k ) < o ) {
							l.vertical = "middle";
						}
						if ( c( d( e ), d( f ) ) > c( d( i ), d( k ) ) ) {
							l.important = "horizontal";
						} else {
							l.important = "vertical";
						}
						b.using.call( this, a, l );
					};
				}
				g.offset( a.extend( z, {
					using: f
				} ) );
			} );
		};
		a.ui.pos = {
			_trigger: function( a, b, c, d ) {
				if ( b.elem ) {
					b.elem.trigger( {
						type: c,
						position: a,
						positionData: b,
						triggered: d
					} );
				}
			},
			fit: {
				left: function( b, d ) {
					a.ui.pos._trigger( b, d, "posCollide", "fitLeft" );
					var e = d.within, f = e.isWindow ? e.scrollLeft : e.offset.left, g = e.width, h = b.left - d.collisionPosition.marginLeft, i = f - h, j = h + d.collisionWidth - g - f, k;
					if ( d.collisionWidth > g ) {
						if ( i > 0 && j <= 0 ) {
							k = b.left + i + d.collisionWidth - g - f;
							b.left += i - k;
						} else if ( j > 0 && i <= 0 ) {
							b.left = f;
						} else {
							if ( i > j ) {
								b.left = f + g - d.collisionWidth;
							} else {
								b.left = f;
							}
						}
					} else if ( i > 0 ) {
						b.left += i;
					} else if ( j > 0 ) {
						b.left -= j;
					} else {
						b.left = c( b.left - h, b.left );
					}
					a.ui.pos._trigger( b, d, "posCollided", "fitLeft" );
				},
				top: function( b, d ) {
					a.ui.pos._trigger( b, d, "posCollide", "fitTop" );
					var e = d.within, f = e.isWindow ? e.scrollTop : e.offset.top, g = d.within.height, h = b.top - d.collisionPosition.marginTop, i = f - h, j = h + d.collisionHeight - g - f, k;
					if ( d.collisionHeight > g ) {
						if ( i > 0 && j <= 0 ) {
							k = b.top + i + d.collisionHeight - g - f;
							b.top += i - k;
						} else if ( j > 0 && i <= 0 ) {
							b.top = f;
						} else {
							if ( i > j ) {
								b.top = f + g - d.collisionHeight;
							} else {
								b.top = f;
							}
						}
					} else if ( i > 0 ) {
						b.top += i;
					} else if ( j > 0 ) {
						b.top -= j;
					} else {
						b.top = c( b.top - h, b.top );
					}
					a.ui.pos._trigger( b, d, "posCollided", "fitTop" );
				}
			},
			flip: {
				left: function( b, c ) {
					a.ui.pos._trigger( b, c, "posCollide", "flipLeft" );
					var e = c.within, f = e.offset.left + e.scrollLeft, g = e.width, h = e.isWindow ? e.scrollLeft : e.offset.left, i = b.left - c.collisionPosition.marginLeft, j = i - h, k = i + c.collisionWidth - g - h, l = c.my[0] === "left" ? -c.elemWidth : c.my[0] === "right" ? c.elemWidth : 0, m = c.at[0] === "left" ? c.targetWidth : c.at[0] === "right" ? -c.targetWidth : 0, n = -2 * c.offset[0], o, p;
					if ( j < 0 ) {
						o = b.left + l + m + n + c.collisionWidth - g - f;
						if ( o < 0 || o < d( j ) ) {
							b.left += l + m + n;
						}
					} else if ( k > 0 ) {
						p = b.left - c.collisionPosition.marginLeft + l + m + n - h;
						if ( p > 0 || d( p ) < k ) {
							b.left += l + m + n;
						}
					}
					a.ui.pos._trigger( b, c, "posCollided", "flipLeft" );
				},
				top: function( b, c ) {
					a.ui.pos._trigger( b, c, "posCollide", "flipTop" );
					var e = c.within, f = e.offset.top + e.scrollTop, g = e.height, h = e.isWindow ? e.scrollTop : e.offset.top, i = b.top - c.collisionPosition.marginTop, j = i - h, k = i + c.collisionHeight - g - h, l = c.my[1] === "top", m = l ? -c.elemHeight : c.my[1] === "bottom" ? c.elemHeight : 0, n = c.at[1] === "top" ? c.targetHeight : c.at[1] === "bottom" ? -c.targetHeight : 0, o = -2 * c.offset[1], p, q;
					if ( j < 0 ) {
						q = b.top + m + n + o + c.collisionHeight - g - f;
						if ( q < 0 || q < d( j ) ) {
							b.top += m + n + o;
						}
					} else if ( k > 0 ) {
						p = b.top - c.collisionPosition.marginTop + m + n + o - h;
						if ( p > 0 || d( p ) < k ) {
							b.top += m + n + o;
						}
					}
					a.ui.pos._trigger( b, c, "posCollided", "flipTop" );
				}
			},
			flipfit: {
				left: function() {
					a.ui.pos.flip.left.apply( this, arguments );
					a.ui.pos.fit.left.apply( this, arguments );
				},
				top: function() {
					a.ui.pos.flip.top.apply( this, arguments );
					a.ui.pos.fit.top.apply( this, arguments );
				}
			}
		};
		( function() {
			var b, c, d, e, f, g = document.getElementsByTagName( "body" )[0], h = document.createElement( "div" );
			b = document.createElement( g ? "div" : "body" );
			d = {
				visibility: "hidden",
				width: 0,
				height: 0,
				border: 0,
				margin: 0,
				background: "none"
			};
			if ( g ) {
				a.extend( d, {
					position: "absolute",
					left: "-1000px",
					top: "-1000px"
				} );
			}
			for ( f in d ) {
				b.style[f] = d[f];
			}
			b.appendChild( h );
			c = g || document.documentElement;
			c.insertBefore( b, c.firstChild );
			h.style.cssText = "position: absolute; left: 10.7432222px;";
			e = a( h ).offset().left;
			a.support.offsetFractions = e > 10 && e < 11;
			b.innerHTML = "";
			c.removeChild( b );
		} )();
	} )();
	var c = a.ui.position;
} );

( function( a ) {
	"use strict";
	if ( typeof define === "function" && define.amd ) {
		define( [ "jquery" ], a );
	} else if ( window.jQuery && !window.jQuery.fn.iconpicker ) {
		a( window.jQuery );
	}
} )( function( a ) {
	"use strict";
	var b = {
		isEmpty: function( a ) {
			return a === false || a === "" || a === null || a === undefined;
		},
		isEmptyObject: function( a ) {
			return this.isEmpty( a ) === true || a.length === 0;
		},
		isElement: function( b ) {
			return a( b ).length > 0;
		},
		isString: function( a ) {
			return typeof a === "string" || a instanceof String;
		},
		isArray: function( b ) {
			return a.isArray( b );
		},
		inArray: function( b, c ) {
			return a.inArray( b, c ) !== -1;
		},
		throwError: function( a ) {
			throw "Font Awesome Icon Picker Exception: " + a;
		}
	};
	var c = function( d, e ) {
		this._id = c._idCounter++;
		this.element = a( d ).addClass( "iconpicker-element" );
		this._trigger( "iconpickerCreate", {
			iconpickerValue: this.iconpickerValue
		} );
		this.options = a.extend( { }, c.defaultOptions, this.element.data(), e );
		this.options.templates = a.extend( { }, c.defaultOptions.templates, this.options.templates );
		this.options.originalPlacement = this.options.placement;
		this.container = b.isElement( this.options.container ) ? a( this.options.container ) : false;
		if ( this.container === false ) {
			if ( this.element.is( ".dropdown-toggle" ) ) {
				this.container = a( "~ .dropdown-menu:first", this.element );
			} else {
				this.container = this.element.is( "input,textarea,button,.btn" ) ? this.element.parent() : this.element;
			}
		}
		this.container.addClass( "iconpicker-container" );
		if ( this.isDropdownMenu() ) {
			this.options.placement = "inline";
		}
		this.input = this.element.is( "input,textarea" ) ? this.element.addClass( "iconpicker-input" ) : false;
		if ( this.input === false ) {
			this.input = this.container.find( this.options.input );
			if ( !this.input.is( "input,textarea" ) ) {
				this.input = false;
			}
		}
		this.component = this.isDropdownMenu() ? this.container.parent().find( this.options.component ) : this.container.find( this.options.component );
		if ( this.component.length === 0 ) {
			this.component = false;
		} else {
			this.component.find( "i" ).addClass( "iconpicker-component" );
		}
		this._createPopover();
		this._createIconpicker();
		if ( this.getAcceptButton().length === 0 ) {
			this.options.mustAccept = false;
		}
		if ( this.isInputGroup() ) {
			this.container.parent().append( this.popover );
		} else {
			this.container.append( this.popover );
		}
		this._bindElementEvents();
		this._bindWindowEvents();
		this.update( this.options.selected );
		if ( this.isInline() ) {
			this.show();
		}
		this._trigger( "iconpickerCreated", {
			iconpickerValue: this.iconpickerValue
		} );
	};
	c._idCounter = 0;
	c.defaultOptions = {
		title: false,
		selected: false,
		defaultValue: false,
		placement: "bottom",
		collision: "none",
		animation: true,
		hideOnSelect: false,
		showFooter: false,
		searchInFooter: false,
		mustAccept: false,
		selectedCustomClass: "bg-primary",
		icons: [ ],
		fullClassFormatter: function( a ) {
			return a;
		},
		input: "input,.iconpicker-input",
		inputSearch: false,
		container: false,
		component: ".input-group-addon,.iconpicker-component",
		templates: {
			popover: '<div class="iconpicker-popover popover"><div class="arrow"></div>' + '<div class="popover-title"></div><div class="popover-content"></div></div>',
			footer: '<div class="popover-footer"></div>',
			buttons: '<button class="iconpicker-btn iconpicker-btn-cancel btn btn-default btn-sm">Cancel</button>' + ' <button class="iconpicker-btn iconpicker-btn-accept btn btn-primary btn-sm">Accept</button>',
			search: '<input type="search" class="form-control iconpicker-search" placeholder="Поиск..." />',
			iconpicker: '<div class="iconpicker"><div class="iconpicker-items"></div></div>',
			iconpickerItem: '<span role="button" class="iconpicker-item"><i></i></span>'
		}
	};
	c.batch = function( b, c ) {
		var d = Array.prototype.slice.call( arguments, 2 );
		return a( b ).each( function() {
			var b = a( this ).data( "iconpicker" );
			if ( !!b ) {
				b[c].apply( b, d );
			}
		} );
	};
	c.prototype = {
		constructor: c,
		options: { },
		_id: 0,
		_trigger: function( b, c ) {
			c = c || { };
			this.element.trigger( a.extend( {
				type: b,
				iconpickerInstance: this
			},
				c ) );
		},
		_createPopover: function() {
			this.popover = a( this.options.templates.popover );
			var c = this.popover.find( ".popover-title" );
			if ( !!this.options.title ) {
				c.append( a( '<div class="popover-title-text">' + this.options.title + "</div>" ) );
			}
			if ( this.hasSeparatedSearchInput() && !this.options.searchInFooter ) {
				c.append( this.options.templates.search );
			} else if ( !this.options.title ) {
				c.remove();
			}
			if ( this.options.showFooter && !b.isEmpty( this.options.templates.footer ) ) {
				var d = a( this.options.templates.footer );
				if ( this.hasSeparatedSearchInput() && this.options.searchInFooter ) {
					d.append( a( this.options.templates.search ) );
				}
				if ( !b.isEmpty( this.options.templates.buttons ) ) {
					d.append( a( this.options.templates.buttons ) );
				}
				this.popover.append( d );
			}
			if ( this.options.animation === true ) {
				this.popover.addClass( "fade" );
			}
			return this.popover;
		},
		_createIconpicker: function() {
			var b = this;
			this.iconpicker = a( this.options.templates.iconpicker );
			var c = function( c ) {
				var d = a( this );
				if ( d.is( "i" ) ) {
					d = d.parent();
				}
				b._trigger( "iconpickerSelect", {
					iconpickerItem: d,
					iconpickerValue: b.iconpickerValue
				} );
				if ( b.options.mustAccept === false ) {
					b.update( d.data( "iconpickerValue" ) );
					b._trigger( "iconpickerSelected", {
						iconpickerItem: this,
						iconpickerValue: b.iconpickerValue
					} );
				} else {
					b.update( d.data( "iconpickerValue" ), true );
				}
				if ( b.options.hideOnSelect && b.options.mustAccept === false ) {
					b.hide();
				}
			};
			for ( var d in this.options.icons ) {
				if ( typeof this.options.icons[d].title === "string" ) {
					var e = a( this.options.templates.iconpickerItem );
					e.find( "i" ).addClass( 'rcli ' + this.options.fullClassFormatter( this.options.icons[d].title ) );
					e.data( "iconpickerValue", this.options.icons[d].title ).on( "click.iconpicker", c );
					this.iconpicker.find( ".iconpicker-items" ).append( e.attr( "title", "." + this.options.icons[d].title ) );
					if ( this.options.icons[d].searchTerms.length > 0 ) {
						var f = "";
						for ( var g = 0; g < this.options.icons[d].searchTerms.length; g++ ) {
							f = f + this.options.icons[d].searchTerms[g] + " ";
						}
						this.iconpicker.find( ".iconpicker-items" ).append( e.attr( "data-search-terms", f ) );
					}
				}
			}
			this.popover.find( ".popover-content" ).append( this.iconpicker );
			return this.iconpicker;
		},
		_isEventInsideIconpicker: function( b ) {
			var c = a( b.target );
			if ( ( !c.hasClass( "iconpicker-element" ) || c.hasClass( "iconpicker-element" ) && !c.is( this.element ) ) && c.parents( ".iconpicker-popover" ).length === 0 ) {
				return false;
			}
			return true;
		},
		_bindElementEvents: function() {
			var c = this;
			this.getSearchInput().on( "keyup.iconpicker", function() {
				c.filter( a( this ).val().toLowerCase() );
			} );
			this.getAcceptButton().on( "click.iconpicker", function() {
				var a = c.iconpicker.find( ".iconpicker-selected" ).get( 0 );
				c.update( c.iconpickerValue );
				c._trigger( "iconpickerSelected", {
					iconpickerItem: a,
					iconpickerValue: c.iconpickerValue
				} );
				if ( !c.isInline() ) {
					c.hide();
				}
			} );
			this.getCancelButton().on( "click.iconpicker", function() {
				if ( !c.isInline() ) {
					c.hide();
				}
			} );
			this.element.on( "focus.iconpicker", function( a ) {
				c.show();
				a.stopPropagation();
			} );
			if ( this.hasComponent() ) {
				this.component.on( "click.iconpicker", function() {
					c.toggle();
				} );
			}
			if ( this.hasInput() ) {
				this.input.on( "keyup.iconpicker", function( d ) {
					if ( !b.inArray( d.keyCode, [ 38, 40, 37, 39, 16, 17, 18,
						9, 8, 91, 93,
						20, 46, 186, 190, 46, 78, 188, 44, 86 ] ) ) {
						c.update();
					} else {
						c._updateFormGroupStatus( c.getValid( this.value ) !== false );
					}
					if ( c.options.inputSearch === true ) {
						c.filter( a( this ).val().toLowerCase() );
					}
				} );
			}
		},
		_bindWindowEvents: function() {
			var b = a( window.document );
			var c = this;
			var d = ".iconpicker.inst" + this._id;
			a( window ).on( "resize.iconpicker" + d + " orientationchange.iconpicker" + d, function( a ) {
				if ( c.popover.hasClass( "in" ) ) {
					c.updatePlacement();
				}
			} );
			if ( !c.isInline() ) {
				b.on( "mouseup" + d, function( a ) {
					if ( !c._isEventInsideIconpicker( a ) && !c.isInline() ) {
						c.hide();
					}
				} );
			}
		},
		_unbindElementEvents: function() {
			this.popover.off( ".iconpicker" );
			this.element.off( ".iconpicker" );
			if ( this.hasInput() ) {
				this.input.off( ".iconpicker" );
			}
			if ( this.hasComponent() ) {
				this.component.off( ".iconpicker" );
			}
			if ( this.hasContainer() ) {
				this.container.off( ".iconpicker" );
			}
		},
		_unbindWindowEvents: function() {
			a( window ).off( ".iconpicker.inst" + this._id );
			a( window.document ).off( ".iconpicker.inst" + this._id );
		},
		updatePlacement: function( b, c ) {
			b = b || this.options.placement;
			this.options.placement = b;
			c = c || this.options.collision;
			c = c === true ? "flip" : c;
			var d = {
				at: "right bottom",
				my: "right top",
				of: this.hasInput() && !this.isInputGroup() ? this.input : this.container,
				collision: c === true ? "flip" : c,
				within: window
			};
			this.popover.removeClass( "inline topLeftCorner topLeft top topRight topRightCorner " + "rightTop right rightBottom bottomRight bottomRightCorner " + "bottom bottomLeft bottomLeftCorner leftBottom left leftTop" );
			if ( typeof b === "object" ) {
				return this.popover.pos( a.extend( { }, d, b ) );
			}
			switch ( b ) {
				case "inline":
					{
						d = false;
					}
					break;

				case "topLeftCorner":
					{
						d.my = "right bottom";
						d.at = "left top";
					}
					break;

				case "topLeft":
					{
						d.my = "left bottom";
						d.at = "left top";
					}
					break;

				case "top":
					{
						d.my = "center bottom";
						d.at = "center top";
					}
					break;

				case "topRight":
					{
						d.my = "right bottom";
						d.at = "right top";
					}
					break;

				case "topRightCorner":
					{
						d.my = "left bottom";
						d.at = "right top";
					}
					break;

				case "rightTop":
					{
						d.my = "left bottom";
						d.at = "right center";
					}
					break;

				case "right":
					{
						d.my = "left center";
						d.at = "right center";
					}
					break;

				case "rightBottom":
					{
						d.my = "left top";
						d.at = "right center";
					}
					break;

				case "bottomRightCorner":
					{
						d.my = "left top";
						d.at = "right bottom";
					}
					break;

				case "bottomRight":
					{
						d.my = "right top";
						d.at = "right bottom";
					}
					break;

				case "bottom":
					{
						d.my = "center top";
						d.at = "center bottom";
					}
					break;

				case "bottomLeft":
					{
						d.my = "left top";
						d.at = "left bottom";
					}
					break;

				case "bottomLeftCorner":
					{
						d.my = "right top";
						d.at = "left bottom";
					}
					break;

				case "leftBottom":
					{
						d.my = "right top";
						d.at = "left center";
					}
					break;

				case "left":
					{
						d.my = "right center";
						d.at = "left center";
					}
					break;

				case "leftTop":
					{
						d.my = "right bottom";
						d.at = "left center";
					}
					break;

				default:
					{
						return false;
					}
					break;
			}
			this.popover.css( {
				display: this.options.placement === "inline" ? "" : "block"
			} );
			if ( d !== false ) {
				this.popover.pos( d ).css( "maxWidth", a( window ).width() - this.container.offset().left - 5 );
			} else {
				this.popover.css( {
					top: "auto",
					right: "auto",
					bottom: "auto",
					left: "auto",
					maxWidth: "none"
				} );
			}
			this.popover.addClass( this.options.placement );
			return true;
		},
		_updateComponents: function() {
			this.iconpicker.find( ".iconpicker-item.iconpicker-selected" ).removeClass( "iconpicker-selected " + this.options.selectedCustomClass );
			if ( this.iconpickerValue ) {
				this.iconpicker.find( "." + this.options.fullClassFormatter( this.iconpickerValue ).replace( / /g, "." ) ).parent().addClass( "iconpicker-selected " + this.options.selectedCustomClass );
			}
			if ( this.hasComponent() ) {
				var a = this.component.find( "i" );
				if ( a.length > 0 ) {
					a.attr( "class", this.options.fullClassFormatter( this.iconpickerValue ) );
				} else {
					this.component.html( this.getHtml() );
				}
			}
		},
		_updateFormGroupStatus: function( a ) {
			if ( this.hasInput() ) {
				if ( a !== false ) {
					this.input.parents( ".form-group:first" ).removeClass( "has-error" );
				} else {
					this.input.parents( ".form-group:first" ).addClass( "has-error" );
				}
				return true;
			}
			return false;
		},
		getValid: function( c ) {
			if ( !b.isString( c ) ) {
				c = "";
			}
			var d = c === "";
			c = a.trim( c );
			var e = false;
			for ( var f = 0; f < this.options.icons.length; f++ ) {
				if ( this.options.icons[f].title === c ) {
					e = true;
					break;
				}
			}
			if ( e || d ) {
				return c;
			}
			return false;
		},
		setValue: function( a ) {
			var b = this.getValid( a );
			if ( b !== false ) {
				this.iconpickerValue = b;
				this._trigger( "iconpickerSetValue", {
					iconpickerValue: b
				} );
				return this.iconpickerValue;
			} else {
				this._trigger( "iconpickerInvalid", {
					iconpickerValue: a
				} );
				return false;
			}
		},
		getHtml: function() {
			return '<i class="' + this.options.fullClassFormatter( this.iconpickerValue ) + '"></i>';
		},
		setSourceValue: function( a ) {
			a = this.setValue( a );
			if ( a !== false && a !== "" ) {
				if ( this.hasInput() ) {
					this.input.val( this.iconpickerValue );
				} else {
					this.element.data( "iconpickerValue", this.iconpickerValue );
				}
				this._trigger( "iconpickerSetSourceValue", {
					iconpickerValue: a
				} );
			}
			return a;
		},
		getSourceValue: function( a ) {
			a = a || this.options.defaultValue;
			var b = a;
			if ( this.hasInput() ) {
				b = this.input.val();
			} else {
				b = this.element.data( "iconpickerValue" );
			}
			if ( b === undefined || b === "" || b === null || b === false ) {
				b = a;
			}
			return b;
		},
		hasInput: function() {
			return this.input !== false;
		},
		isInputSearch: function() {
			return this.hasInput() && this.options.inputSearch === true;
		},
		isInputGroup: function() {
			return this.container.is( ".input-group" );
		},
		isDropdownMenu: function() {
			return this.container.is( ".dropdown-menu" );
		},
		hasSeparatedSearchInput: function() {
			return this.options.templates.search !== false && !this.isInputSearch();
		},
		hasComponent: function() {
			return this.component !== false;
		},
		hasContainer: function() {
			return this.container !== false;
		},
		getAcceptButton: function() {
			return this.popover.find( ".iconpicker-btn-accept" );
		},
		getCancelButton: function() {
			return this.popover.find( ".iconpicker-btn-cancel" );
		},
		getSearchInput: function() {
			return this.popover.find( ".iconpicker-search" );
		},
		filter: function( c ) {
			if ( b.isEmpty( c ) ) {
				this.iconpicker.find( ".iconpicker-item" ).show();
				return a( false );
			} else {
				var d = [ ];
				this.iconpicker.find( ".iconpicker-item" ).each( function() {
					var b = a( this );
					var e = b.attr( "title" ).toLowerCase();
					var f = b.attr( "data-search-terms" ) ? b.attr( "data-search-terms" ).toLowerCase() : "";
					e = e + " " + f;
					var g = false;
					try {
						g = new RegExp( "(^|\\W)" + c, "g" );
					} catch ( a ) {
						g = false;
					}
					if ( g !== false && e.match( g ) ) {
						d.push( b );
						b.show();
					} else {
						b.hide();
					}
				} );
				return d;
			}
		},
		show: function() {
			if ( this.popover.hasClass( "in" ) ) {
				return false;
			}
			a.iconpicker.batch( a( ".iconpicker-popover.in:not(.inline)" ).not( this.popover ), "hide" );
			this._trigger( "iconpickerShow", {
				iconpickerValue: this.iconpickerValue
			} );
			this.updatePlacement();
			this.popover.addClass( "in" );
			setTimeout( a.proxy( function() {
				this.popover.css( "display", this.isInline() ? "" : "block" );
				this._trigger( "iconpickerShown", {
					iconpickerValue: this.iconpickerValue
				} );
			}, this ), this.options.animation ? 300 : 1 );
		},
		hide: function() {
			if ( !this.popover.hasClass( "in" ) ) {
				return false;
			}
			this._trigger( "iconpickerHide", {
				iconpickerValue: this.iconpickerValue
			} );
			this.popover.removeClass( "in" );
			setTimeout( a.proxy( function() {
				this.popover.css( "display", "none" );
				this.getSearchInput().val( "" );
				this.filter( "" );
				this._trigger( "iconpickerHidden", {
					iconpickerValue: this.iconpickerValue
				} );
			}, this ), this.options.animation ? 300 : 1 );
		},
		toggle: function() {
			if ( this.popover.is( ":visible" ) ) {
				this.hide();
			} else {
				this.show( true );
			}
		},
		update: function( a, b ) {
			a = a ? a : this.getSourceValue( this.iconpickerValue );
			this._trigger( "iconpickerUpdate", {
				iconpickerValue: this.iconpickerValue
			} );
			if ( b === true ) {
				a = this.setValue( a );
			} else {
				a = this.setSourceValue( a );
				this._updateFormGroupStatus( a !== false );
			}
			if ( a !== false ) {
				this._updateComponents();
			}
			this._trigger( "iconpickerUpdated", {
				iconpickerValue: this.iconpickerValue
			} );
			return a;
		},
		destroy: function() {
			this._trigger( "iconpickerDestroy", {
				iconpickerValue: this.iconpickerValue
			} );
			this.element.removeData( "iconpicker" ).removeData( "iconpickerValue" ).removeClass( "iconpicker-element" );
			this._unbindElementEvents();
			this._unbindWindowEvents();
			a( this.popover ).remove();
			this._trigger( "iconpickerDestroyed", {
				iconpickerValue: this.iconpickerValue
			} );
		},
		disable: function() {
			if ( this.hasInput() ) {
				this.input.prop( "disabled", true );
				return true;
			}
			return false;
		},
		enable: function() {
			if ( this.hasInput() ) {
				this.input.prop( "disabled", false );
				return true;
			}
			return false;
		},
		isDisabled: function() {
			if ( this.hasInput() ) {
				return this.input.prop( "disabled" ) === true;
			}
			return false;
		},
		isInline: function() {
			return this.options.placement === "inline" || this.popover.hasClass( "inline" );
		}
	};
	a.iconpicker = c;
	a.fn.iconpicker = function( b ) {
		return this.each( function() {
			var d = a( this );
			if ( !d.data( "iconpicker" ) ) {
				d.data( "iconpicker", new c( this, typeof b === "object" ? b : { } ) );
			}
		} );
	};
	c.defaultOptions = a.extend( c.defaultOptions, {
		icons: [ {
				title: "fa-500px",
				searchTerms: [ ]
			}, {
				title: "fa-address-book",
				searchTerms: [ ]
			}, {
				title: "fa-address-book-o",
				searchTerms: [ ]
			}, {
				title: "fa-address-card",
				searchTerms: [ ]
			}, {
				title: "fa-address-card-o",
				searchTerms: [ ]
			}, {
				title: "fa-adjust",
				searchTerms: [ "contrast" ]
			}, {
				title: "fa-adn",
				searchTerms: [ ]
			}, {
				title: "fa-align-center",
				searchTerms: [ "middle", "text" ]
			}, {
				title: "fa-align-justify",
				searchTerms: [ "text" ]
			}, {
				title: "fa-align-left",
				searchTerms: [ "text" ]
			}, {
				title: "fa-align-right",
				searchTerms: [ "text" ]
			}, {
				title: "fa-amazon",
				searchTerms: [ ]
			}, {
				title: "fa-ambulance",
				searchTerms: [ "vehicle", "support", "help" ]
			}, {
				title: "fa-asl-interpreting",
				searchTerms: [ ]
			}, {
				title: "fa-anchor",
				searchTerms: [ "link" ]
			}, {
				title: "fa-android",
				searchTerms: [ "robot" ]
			}, {
				title: "fa-angellist",
				searchTerms: [ ]
			}, {
				title: "fa-angle-double-down",
				searchTerms: [ "arrows" ]
			}, {
				title: "fa-angle-double-left",
				searchTerms: [ "laquo", "quote", "previous", "back", "arrows" ]
			}, {
				title: "fa-angle-double-right",
				searchTerms: [ "raquo", "quote", "next", "forward", "arrows" ]
			}, {
				title: "fa-angle-double-up",
				searchTerms: [ "arrows" ]
			}, {
				title: "fa-angle-down",
				searchTerms: [ "arrow" ]
			}, {
				title: "fa-angle-left",
				searchTerms: [ "previous", "back", "arrow" ]
			}, {
				title: "fa-angle-right",
				searchTerms: [ "next", "forward", "arrow" ]
			}, {
				title: "fa-angle-up",
				searchTerms: [ "arrow" ]
			}, {
				title: "fa-apple",
				searchTerms: [ "osx", "food" ]
			}, {
				title: "fa-archive",
				searchTerms: [ "box", "storage", "package" ]
			}, {
				title: "fa-arrow-circle-o-down",
				searchTerms: [ "download", "arrow-circle-o-down" ]
			}, {
				title: "fa-arrow-circle-o-left",
				searchTerms: [ "previous", "back", "arrow-circle-o-left" ]
			}, {
				title: "fa-arrow-circle-o-right",
				searchTerms: [ "next", "forward", "arrow-circle-o-right" ]
			}, {
				title: "fa-arrow-circle-o-up",
				searchTerms: [ "arrow-circle-o-up" ]
			}, {
				title: "fa-arrow-circle-down",
				searchTerms: [ "download" ]
			}, {
				title: "fa-arrow-circle-left",
				searchTerms: [ "previous", "back" ]
			}, {
				title: "fa-arrow-circle-right",
				searchTerms: [ "next", "forward" ]
			}, {
				title: "fa-arrow-circle-up",
				searchTerms: [ ]
			}, {
				title: "fa-arrow-down",
				searchTerms: [ "download" ]
			}, {
				title: "fa-arrow-left",
				searchTerms: [ "previous", "back" ]
			}, {
				title: "fa-arrow-right",
				searchTerms: [ "next", "forward" ]
			}, {
				title: "fa-arrow-up",
				searchTerms: [ ]
			}, {
				title: "fa-arrows",
				searchTerms: [ "expand", "enlarge", "fullscreen", "bigger",
					"move",
					"reorder", "resize", "arrow", "arrows" ]
			}, {
				title: "fa-arrows-alt",
				searchTerms: [ "expand", "enlarge", "fullscreen", "bigger",
					"move",
					"reorder", "resize", "arrow", "arrows" ]
			}, {
				title: "fa-arrows-h",
				searchTerms: [ "resize", "arrows-h" ]
			}, {
				title: "fa-arrows-v",
				searchTerms: [ "resize", "arrows-v" ]
			}, {
				title: "fa-assistive-listening-systems",
				searchTerms: [ ]
			}, {
				title: "fa-asterisk",
				searchTerms: [ "details" ]
			}, {
				title: "fa-at",
				searchTerms: [ "email", "e-mail" ]
			}, {
				title: "fa-audio-description",
				searchTerms: [ ]
			}, {
				title: "fa-backward",
				searchTerms: [ "rewind", "previous" ]
			}, {
				title: "fa-balance-scale",
				searchTerms: [ ]
			}, {
				title: "fa-ban",
				searchTerms: [ "delete", "remove", "trash", "hide", "block",
					"stop",
					"abort", "cancel", "ban", "prohibit" ]
			}, {
				title: "fa-bandcamp",
				searchTerms: [ ]
			}, {
				title: "fa-barcode",
				searchTerms: [ "scan" ]
			}, {
				title: "fa-navicon",
				searchTerms: [ "menu", "drag", "reorder", "settings", "list",
					"ul", "ol",
					"checklist", "todo", "list", "hamburger" ]
			}, {
				title: "fa-bathtub",
				searchTerms: [ ]
			}, {
				title: "fa-battery-0",
				searchTerms: [ "power", "status" ]
			}, {
				title: "fa-battery",
				searchTerms: [ "power", "status" ]
			}, {
				title: "fa-battery-2",
				searchTerms: [ "power", "status" ]
			}, {
				title: "fa-battery-1",
				searchTerms: [ "power", "status" ]
			}, {
				title: "fa-battery-3",
				searchTerms: [ "power", "status" ]
			}, {
				title: "fa-battery-4",
				searchTerms: [ "power", "status" ]
			}, {
				title: "fa-hotel",
				searchTerms: [ "travel" ]
			}, {
				title: "fa-beer",
				searchTerms: [ "alcohol", "stein", "drink", "mug", "bar",
					"liquor" ]
			}, {
				title: "fa-behance",
				searchTerms: [ ]
			}, {
				title: "fa-behance-square",
				searchTerms: [ ]
			}, {
				title: "fa-bell",
				searchTerms: [ "alert", "reminder", "notification" ]
			}, {
				title: "fa-bell-o",
				searchTerms: [ "alert", "reminder", "notification" ]
			}, {
				title: "fa-bell-slash",
				searchTerms: [ ]
			}, {
				title: "fa-bell-slash-o",
				searchTerms: [ ]
			}, {
				title: "fa-bicycle",
				searchTerms: [ "vehicle", "bike", "gears" ]
			}, {
				title: "fa-binoculars",
				searchTerms: [ ]
			}, {
				title: "fa-birthday-cake",
				searchTerms: [ ]
			}, {
				title: "fa-bitbucket",
				searchTerms: [ "git", "bitbucket" ]
			}, {
				title: "fa-bitbucket-square",
				searchTerms: [ "git", "bitbucket" ]
			}, {
				title: "fa-bitcoin",
				searchTerms: [ ]
			}, {
				title: "fa-black-tie",
				searchTerms: [ ]
			}, {
				title: "fa-blind",
				searchTerms: [ ]
			}, {
				title: "fa-bluetooth",
				searchTerms: [ ]
			}, {
				title: "fa-bluetooth-b",
				searchTerms: [ ]
			}, {
				title: "fa-bold",
				searchTerms: [ ]
			}, {
				title: "fa-flash",
				searchTerms: [ "lightning", "weather" ]
			}, {
				title: "fa-bomb",
				searchTerms: [ ]
			}, {
				title: "fa-book",
				searchTerms: [ "read", "documentation" ]
			}, {
				title: "fa-bookmark",
				searchTerms: [ "save" ]
			}, {
				title: "fa-bookmark-o",
				searchTerms: [ "save" ]
			}, {
				title: "fa-braille",
				searchTerms: [ ]
			}, {
				title: "fa-briefcase",
				searchTerms: [ "work", "business", "office", "luggage", "bag" ]
			}, {
				title: "fa-btc",
				searchTerms: [ ]
			}, {
				title: "fa-bug",
				searchTerms: [ "report", "insect" ]
			}, {
				title: "fa-building",
				searchTerms: [ "work", "business", "apartment", "office",
					"company" ]
			}, {
				title: "fa-building-o",
				searchTerms: [ "work", "business", "apartment", "office",
					"company" ]
			}, {
				title: "fa-bullhorn",
				searchTerms: [ "announcement", "share", "broadcast", "louder", "megaphone"
				]
			}, {
				title: "fa-bullseye",
				searchTerms: [ "target" ]
			}, {
				title: "fa-bus",
				searchTerms: [ "vehicle" ]
			}, {
				title: "fa-buysellads",
				searchTerms: [ ]
			}, {
				title: "fa-calculator",
				searchTerms: [ ]
			}, {
				title: "fa-calendar",
				searchTerms: [ "date", "time", "when", "event", "calendar-o" ]
			}, {
				title: "fa-calendar-o",
				searchTerms: [ "date", "time", "when", "event", "calendar" ]
			}, {
				title: "fa-calendar-check-o",
				searchTerms: [ "ok" ]
			}, {
				title: "fa-calendar-minus-o",
				searchTerms: [ ]
			}, {
				title: "fa-calendar-plus-o",
				searchTerms: [ ]
			}, {
				title: "fa-calendar-times-o",
				searchTerms: [ ]
			}, {
				title: "fa-camera",
				searchTerms: [ "photo", "picture", "record" ]
			}, {
				title: "fa-camera-retro",
				searchTerms: [ "photo", "picture", "record" ]
			}, {
				title: "fa-automobile",
				searchTerms: [ "vehicle" ]
			}, {
				title: "fa-caret-down",
				searchTerms: [ "more", "dropdown", "menu", "triangle down",
					"arrow" ]
			}, {
				title: "fa-caret-left",
				searchTerms: [ "previous", "back", "triangle left", "arrow" ]
			}, {
				title: "fa-caret-right",
				searchTerms: [ "next", "forward", "triangle right", "arrow" ]
			}, {
				title: "fa-caret-up",
				searchTerms: [ "triangle up", "arrow" ]
			}, {
				title: "fa-toggle-down",
				searchTerms: [ "more", "dropdown", "menu",
					"caret-square-o-down" ]
			}, {
				title: "fa-toggle-left",
				searchTerms: [ "previous", "back", "caret-square-o-left" ]
			}, {
				title: "fa-toggle-right",
				searchTerms: [ "next", "forward", "caret-square-o-right" ]
			}, {
				title: "fa-toggle-up",
				searchTerms: [ "caret-square-o-up" ]
			}, {
				title: "fa-cart-arrow-down",
				searchTerms: [ "shopping" ]
			}, {
				title: "fa-cart-plus",
				searchTerms: [ "add", "shopping" ]
			}, {
				title: "fa-cc-amex",
				searchTerms: [ "amex" ]
			}, {
				title: "fa-cc-diners-club",
				searchTerms: [ ]
			}, {
				title: "fa-cc-discover",
				searchTerms: [ ]
			}, {
				title: "fa-cc-jcb",
				searchTerms: [ ]
			}, {
				title: "fa-cc-mastercard",
				searchTerms: [ ]
			}, {
				title: "fa-cc-paypal",
				searchTerms: [ ]
			}, {
				title: "fa-cc-stripe",
				searchTerms: [ ]
			}, {
				title: "fa-cc-visa",
				searchTerms: [ ]
			}, {
				title: "fa-certificate",
				searchTerms: [ "badge", "star" ]
			}, {
				title: "fa-area-chart",
				searchTerms: [ "graph", "analytics", "area-chart" ]
			}, {
				title: "fa-bar-chart",
				searchTerms: [ "graph", "analytics", "bar-chart" ]
			}, {
				title: "fa-line-chart",
				searchTerms: [ "graph", "analytics", "line-chart", "dashboard" ]
			}, {
				title: "fa-pie-chart",
				searchTerms: [ "graph", "analytics", "pie-chart" ]
			}, {
				title: "fa-check",
				searchTerms: [ "checkmark", "done", "todo", "agree", "accept",
					"confirm",
					"tick", "ok", "select" ]
			}, {
				title: "fa-check-circle",
				searchTerms: [ "todo", "done", "agree", "accept", "confirm",
					"ok", "select"
				]
			}, {
				title: "fa-check-circle-o",
				searchTerms: [ "todo", "done", "agree", "accept", "confirm",
					"ok", "select"
				]
			}, {
				title: "fa-check-square",
				searchTerms: [ "checkmark", "done", "todo", "agree", "accept",
					"confirm",
					"ok", "select" ]
			}, {
				title: "fa-check-square-o",
				searchTerms: [ "checkmark", "done", "todo", "agree", "accept",
					"confirm",
					"ok", "select" ]
			}, {
				title: "fa-chevron-circle-down",
				searchTerms: [ "more", "dropdown", "menu", "arrow" ]
			}, {
				title: "fa-chevron-circle-left",
				searchTerms: [ "previous", "back", "arrow" ]
			}, {
				title: "fa-chevron-circle-right",
				searchTerms: [ "next", "forward", "arrow" ]
			}, {
				title: "fa-chevron-circle-up",
				searchTerms: [ "arrow" ]
			}, {
				title: "fa-chevron-down",
				searchTerms: [ ]
			}, {
				title: "fa-chevron-left",
				searchTerms: [ "bracket", "previous", "back" ]
			}, {
				title: "fa-chevron-right",
				searchTerms: [ "bracket", "next", "forward" ]
			}, {
				title: "fa-chevron-up",
				searchTerms: [ ]
			}, {
				title: "fa-child",
				searchTerms: [ ]
			}, {
				title: "fa-chrome",
				searchTerms: [ "browser" ]
			}, {
				title: "fa-circle",
				searchTerms: [ "dot", "notification", "circle-thin" ]
			}, {
				title: "fa-circle-o",
				searchTerms: [ "dot", "notification", "circle-thin" ]
			}, {
				title: "fa-circle-o-notch",
				searchTerms: [ "circle-o-notch" ]
			}, {
				title: "fa-clipboard",
				searchTerms: [ "paste" ]
			}, {
				title: "fa-clock-o",
				searchTerms: [ "watch", "timer", "late", "timestamp", "date" ]
			}, {
				title: "fa-clone",
				searchTerms: [ "copy" ]
			}, {
				title: "fa-cc",
				searchTerms: [ "cc" ]
			}, {
				title: "fa-cloud",
				searchTerms: [ "save" ]
			}, {
				title: "fa-cloud-download",
				searchTerms: [ "cloud-download" ]
			}, {
				title: "fa-cloud-upload",
				searchTerms: [ "cloud-upload" ]
			}, {
				title: "fa-code",
				searchTerms: [ "html", "brackets" ]
			}, {
				title: "fa-code-fork",
				searchTerms: [ "git", "fork", "vcs", "svn", "github", "rebase",
					"version",
					"branch", "code-fork" ]
			}, {
				title: "fa-codepen",
				searchTerms: [ ]
			}, {
				title: "fa-codiepie",
				searchTerms: [ ]
			}, {
				title: "fa-coffee",
				searchTerms: [ "morning", "mug", "breakrclit", "tea", "drink",
					"cafe" ]
			}, {
				title: "fa-gear",
				searchTerms: [ "settings" ]
			}, {
				title: "fa-cog",
				searchTerms: [ "settings", "gear" ]
			}, {
				title: "fa-cogs",
				searchTerms: [ "settings", "gears" ]
			}, {
				title: "fa-gears",
				searchTerms: [ "settings", "gears" ]
			}, {
				title: "fa-columns",
				searchTerms: [ "split", "panes", "dashboard" ]
			}, {
				title: "fa-commenting",
				searchTerms: [ "speech", "notification", "note", "chat",
					"bubble",
					"feedback", "message", "texting", "sms", "conversation" ]
			}, {
				title: "fa-commenting-o",
				searchTerms: [ "speech", "notification", "note", "chat",
					"bubble",
					"feedback", "message", "texting", "sms", "conversation" ]
			}, {
				title: "fa-comment",
				searchTerms: [ "speech", "notification", "note", "chat",
					"bubble",
					"feedback", "message", "texting", "sms", "conversation" ]
			}, {
				title: "fa-comment-o",
				searchTerms: [ "speech", "notification", "note", "chat",
					"bubble",
					"feedback", "message", "texting", "sms", "conversation" ]
			}, {
				title: "fa-comments",
				searchTerms: [ "speech", "notification", "note", "chat",
					"bubble",
					"feedback", "message", "texting", "sms", "conversation" ]
			}, {
				title: "fa-comments-o",
				searchTerms: [ "speech", "notification", "note", "chat",
					"bubble",
					"feedback", "message", "texting", "sms", "conversation" ]
			}, {
				title: "fa-compass",
				searchTerms: [ "sarclii", "directory", "menu", "location" ]
			}, {
				title: "fa-compress",
				searchTerms: [ "collapse", "combine", "contract", "merge",
					"smaller" ]
			}, {
				title: "fa-connectdevelop",
				searchTerms: [ ]
			}, {
				title: "fa-contao",
				searchTerms: [ ]
			}, {
				title: "fa-copy",
				searchTerms: [ "duplicate", "clone", "file", "files-o" ]
			}, {
				title: "fa-copyright",
				searchTerms: [ ]
			}, {
				title: "fa-creative-commons",
				searchTerms: [ ]
			}, {
				title: "fa-credit-card",
				searchTerms: [ "money", "buy", "debit", "checkout", "purchase",
					"payment",
					"credit-card-alt" ]
			}, {
				title: "fa-crop",
				searchTerms: [ "design" ]
			}, {
				title: "fa-crosshairs",
				searchTerms: [ "picker", "gpd" ]
			}, {
				title: "fa-css3",
				searchTerms: [ "code" ]
			}, {
				title: "fa-cube",
				searchTerms: [ "package" ]
			}, {
				title: "fa-cubes",
				searchTerms: [ "packages" ]
			}, {
				title: "fa-cut",
				searchTerms: [ "scissors", "scissors" ]
			}, {
				title: "fa-dashcube",
				searchTerms: [ ]
			}, {
				title: "fa-database",
				searchTerms: [ ]
			}, {
				title: "fa-deafness",
				searchTerms: [ ]
			}, {
				title: "fa-delicious",
				searchTerms: [ ]
			}, {
				title: "fa-desktop",
				searchTerms: [ "monitor", "screen", "desktop", "computer",
					"demo",
					"device", "pc" ]
			}, {
				title: "fa-deviantart",
				searchTerms: [ ]
			}, {
				title: "fa-digg",
				searchTerms: [ ]
			}, {
				title: "fa-dollar",
				searchTerms: [ "usd", "price" ]
			}, {
				title: "fa-dot-circle-o",
				searchTerms: [ "target", "bullseye", "notification" ]
			}, {
				title: "fa-download",
				searchTerms: [ "import" ]
			}, {
				title: "fa-dribbble",
				searchTerms: [ ]
			}, {
				title: "fa-dropbox",
				searchTerms: [ ]
			}, {
				title: "fa-drupal",
				searchTerms: [ ]
			}, {
				title: "fa-edge",
				searchTerms: [ "browser", "ie" ]
			}, {
				title: "fa-edit",
				searchTerms: [ "write", "edit", "update", "pencil", "pen" ]
			}, {
				title: "fa-eject",
				searchTerms: [ ]
			}, {
				title: "fa-ellipsis-h",
				searchTerms: [ "dots" ]
			}, {
				title: "fa-ellipsis-v",
				searchTerms: [ "dots" ]
			}, {
				title: "fa-ge",
				searchTerms: [ ]
			}, {
				title: "fa-envelope",
				searchTerms: [ "email", "e-mail", "letter", "support", "mail",
					"message",
					"notification" ]
			}, {
				title: "fa-envelope-o",
				searchTerms: [ "email", "e-mail", "letter", "support", "mail",
					"message",
					"notification" ]
			}, {
				title: "fa-envelope-open",
				searchTerms: [ "email", "e-mail", "letter", "support", "mail",
					"message",
					"notification" ]
			}, {
				title: "fa-envelope-open-o",
				searchTerms: [ "email", "e-mail", "letter", "support", "mail",
					"message",
					"notification" ]
			}, {
				title: "fa-envelope-square",
				searchTerms: [ "email", "e-mail", "letter", "support", "mail",
					"message",
					"notification" ]
			}, {
				title: "fa-envira",
				searchTerms: [ "leaf" ]
			}, {
				title: "fa-eraser",
				searchTerms: [ "remove", "delete" ]
			}, {
				title: "fa-etsy",
				searchTerms: [ ]
			}, {
				title: "fa-eur",
				searchTerms: [ "eur", "euro" ]
			}, {
				title: "fa-euro",
				searchTerms: [ "eur", "euro" ]
			}, {
				title: "fa-exchange",
				searchTerms: [ "transfer", "arrows", "arrow", "exchange",
					"swap" ]
			}, {
				title: "fa-exclamation",
				searchTerms: [ "warning", "error", "problem", "notification",
					"notify",
					"alert", "danger" ]
			}, {
				title: "fa-exclamation-circle",
				searchTerms: [ "warning", "error", "problem", "notification",
					"notify",
					"alert", "danger" ]
			}, {
				title: "fa-warning",
				searchTerms: [ "warning", "error", "problem", "notification",
					"notify",
					"alert", "danger" ]
			}, {
				title: "fa-expand",
				searchTerms: [ "enlarge", "bigger", "resize" ]
			}, {
				title: "fa-expeditedssl",
				searchTerms: [ ]
			}, {
				title: "fa-external-link",
				searchTerms: [ "open", "new", "external-link" ]
			}, {
				title: "fa-external-link-square",
				searchTerms: [ "open", "new", "external-link-square" ]
			}, {
				title: "fa-eye",
				searchTerms: [ "show", "visible", "views" ]
			}, {
				title: "fa-eyedropper",
				searchTerms: [ "eyedropper" ]
			}, {
				title: "fa-eye-slash",
				searchTerms: [ "toggle", "show", "hide", "visible",
					"visiblity", "views" ]
			}, {
				title: "fa-facebook",
				searchTerms: [ "social network", "facebook-official" ]
			}, {
				title: "fa-facebook-square",
				searchTerms: [ "social network" ]
			}, {
				title: "fa-fast-backward",
				searchTerms: [ "rewind", "previous", "beginning", "start",
					"first" ]
			}, {
				title: "fa-fast-forward",
				searchTerms: [ "next", "end", "last" ]
			}, {
				title: "fa-fax",
				searchTerms: [ ]
			}, {
				title: "fa-female",
				searchTerms: [ "woman", "human", "user", "person", "profile" ]
			}, {
				title: "fa-fighter-jet",
				searchTerms: [ "fly", "plane", "airplane", "quick", "rclit",
					"travel" ]
			}, {
				title: "fa-file-o",
				searchTerms: [ "new", "page", "pdf", "document" ]
			}, {
				title: "fa-file-text",
				searchTerms: [ "new", "page", "pdf", "document", "file-text" ]
			}, {
				title: "fa-file-text-o",
				searchTerms: [ "new", "page", "pdf", "document", "file-text" ]
			}, {
				title: "fa-file-archive-o",
				searchTerms: [ ]
			}, {
				title: "fa-file-audio-o",
				searchTerms: [ ]
			}, {
				title: "fa-file-code-o",
				searchTerms: [ ]
			}, {
				title: "fa-file-excel-o",
				searchTerms: [ ]
			}, {
				title: "fa-file-picture-o",
				searchTerms: [ ]
			}, {
				title: "fa-file-pdf-o",
				searchTerms: [ ]
			}, {
				title: "fa-file-powerpoint-o",
				searchTerms: [ ]
			}, {
				title: "fa-file-video-o",
				searchTerms: [ ]
			}, {
				title: "fa-file-word-o",
				searchTerms: [ ]
			}, {
				title: "fa-filter",
				searchTerms: [ "funnel", "options" ]
			}, {
				title: "fa-fire",
				searchTerms: [ "flame", "hot", "popular" ]
			}, {
				title: "fa-fire-extinguisher",
				searchTerms: [ ]
			}, {
				title: "fa-firefox",
				searchTerms: [ "browser" ]
			}, {
				title: "fa-first-order",
				searchTerms: [ ]
			}, {
				title: "fa-flag",
				searchTerms: [ "report", "notification", "notify" ]
			}, {
				title: "fa-flag-o",
				searchTerms: [ "report", "notification", "notify" ]
			}, {
				title: "fa-flag-checkered",
				searchTerms: [ "report", "notification", "notify" ]
			}, {
				title: "fa-flask",
				searchTerms: [ "science", "beaker", "experimental", "labs" ]
			}, {
				title: "fa-flickr",
				searchTerms: [ ]
			}, {
				title: "fa-folder",
				searchTerms: [ ]
			}, {
				title: "fa-folder-o",
				searchTerms: [ ]
			}, {
				title: "fa-folder-open",
				searchTerms: [ ]
			}, {
				title: "fa-folder-open-o",
				searchTerms: [ ]
			}, {
				title: "fa-font",
				searchTerms: [ "text" ]
			}, {
				title: "fa-fonticons",
				searchTerms: [ ]
			}, {
				title: "fa-fort-awesome",
				searchTerms: [ "castle" ]
			}, {
				title: "fa-forumbee",
				searchTerms: [ ]
			}, {
				title: "fa-forward",
				searchTerms: [ "forward", "next" ]
			}, {
				title: "fa-foursquare",
				searchTerms: [ ]
			}, {
				title: "fa-free-code-camp",
				searchTerms: [ ]
			}, {
				title: "fa-frown-o",
				searchTerms: [ "face", "emoticon", "sad", "disapprove",
					"rating" ]
			}, {
				title: "fa-futbol-o",
				searchTerms: [ ]
			}, {
				title: "fa-gamepad",
				searchTerms: [ "controller" ]
			}, {
				title: "fa-legal",
				searchTerms: [ "judge", "lawyer", "opinion", "hammer" ]
			}, {
				title: "fa-diamond",
				searchTerms: [ "diamond" ]
			}, {
				title: "fa-genderless",
				searchTerms: [ ]
			}, {
				title: "fa-get-pocket",
				searchTerms: [ ]
			}, {
				title: "fa-gg",
				searchTerms: [ ]
			}, {
				title: "fa-gg-circle",
				searchTerms: [ ]
			}, {
				title: "fa-gift",
				searchTerms: [ "present" ]
			}, {
				title: "fa-git",
				searchTerms: [ ]
			}, {
				title: "fa-git-square",
				searchTerms: [ ]
			}, {
				title: "fa-github",
				searchTerms: [ "octocat" ]
			}, {
				title: "fa-github-alt",
				searchTerms: [ "octocat" ]
			}, {
				title: "fa-github-square",
				searchTerms: [ "octocat" ]
			}, {
				title: "fa-gitlab",
				searchTerms: [ "Axosoft" ]
			}, {
				title: "fa-glass",
				searchTerms: [ "martini", "drink", "bar", "alcohol", "liquor",
					"glass" ]
			}, {
				title: "fa-glide",
				searchTerms: [ ]
			}, {
				title: "fa-glide-g",
				searchTerms: [ ]
			}, {
				title: "fa-globe",
				searchTerms: [ "world", "planet", "map", "place", "travel",
					"earth",
					"global", "translate", "all", "language", "localize",
					"location",
					"coordinates", "country", "gps" ]
			}, {
				title: "fa-google",
				searchTerms: [ ]
			}, {
				title: "fa-google-plus",
				searchTerms: [ "google-plus-circle", "google-plus-official" ]
			}, {
				title: "fa-google-plus-official",
				searchTerms: [ "social network", "google-plus" ]
			}, {
				title: "fa-google-plus-square",
				searchTerms: [ "social network" ]
			}, {
				title: "fa-google-wallet",
				searchTerms: [ ]
			}, {
				title: "fa-mortar-board",
				searchTerms: [ "learning", "school", "student" ]
			}, {
				title: "fa-gittip",
				searchTerms: [ "heart", "like", "favorite", "love" ]
			}, {
				title: "fa-grav",
				searchTerms: [ ]
			}, {
				title: "fa-h-square",
				searchTerms: [ "hospital", "hotel" ]
			}, {
				title: "fa-y-combinator-square",
				searchTerms: [ ]
			}, {
				title: "fa-hand-lizard-o",
				searchTerms: [ ]
			}, {
				title: "fa-hand-stop-o",
				searchTerms: [ "stop" ]
			}, {
				title: "fa-hand-peace-o",
				searchTerms: [ ]
			}, {
				title: "fa-hand-o-down",
				searchTerms: [ "point", "finger", "hand-o-down" ]
			}, {
				title: "fa-hand-o-left",
				searchTerms: [ "point", "left", "previous", "back", "finger", "hand-o-left"
				]
			}, {
				title: "fa-hand-o-right",
				searchTerms: [ "point", "right", "next", "forward", "finger",
					"hand-o-right" ]
			}, {
				title: "fa-hand-o-up",
				searchTerms: [ "point", "finger", "hand-o-up" ]
			}, {
				title: "fa-hand-pointer-o",
				searchTerms: [ "select" ]
			}, {
				title: "fa-hand-rock-o",
				searchTerms: [ ]
			}, {
				title: "fa-hand-scissors-o",
				searchTerms: [ ]
			}, {
				title: "fa-hand-spock-o",
				searchTerms: [ ]
			}, {
				title: "fa-handshake-o",
				searchTerms: [ ]
			}, {
				title: "fa-hard-of-hearing",
				searchTerms: [ ]
			}, {
				title: "fa-hashtag",
				searchTerms: [ ]
			}, {
				title: "fa-hdd-o",
				searchTerms: [ "harddrive", "hard drive", "storage", "save" ]
			}, {
				title: "fa-header",
				searchTerms: [ "header", "header" ]
			}, {
				title: "fa-headphones",
				searchTerms: [ "sound", "listen", "music", "audio" ]
			}, {
				title: "fa-heart",
				searchTerms: [ "love", "like", "favorite" ]
			}, {
				title: "fa-heart-o",
				searchTerms: [ "love", "like", "favorite" ]
			}, {
				title: "fa-heartbeat",
				searchTerms: [ "ekg", "vital signs" ]
			}, {
				title: "fa-history",
				searchTerms: [ ]
			}, {
				title: "fa-home",
				searchTerms: [ "main", "house" ]
			}, {
				title: "fa-hospital-o",
				searchTerms: [ "building", "medical center", "emergency room" ]
			}, {
				title: "fa-hourglass",
				searchTerms: [ ]
			}, {
				title: "fa-hourglass-3",
				searchTerms: [ ]
			}, {
				title: "fa-hourglass-2",
				searchTerms: [ ]
			}, {
				title: "fa-hourglass-1",
				searchTerms: [ ]
			}, {
				title: "fa-houzz",
				searchTerms: [ ]
			}, {
				title: "fa-html5",
				searchTerms: [ ]
			}, {
				title: "fa-i-cursor",
				searchTerms: [ ]
			}, {
				title: "fa-id-badge",
				searchTerms: [ ]
			}, {
				title: "fa-id-card",
				searchTerms: [ ]
			}, {
				title: "fa-id-card-o",
				searchTerms: [ ]
			}, {
				title: "fa-image",
				searchTerms: [ "photo", "album", "picture", "image" ]
			}, {
				title: "fa-imdb",
				searchTerms: [ ]
			}, {
				title: "fa-inbox",
				searchTerms: [ ]
			}, {
				title: "fa-indent",
				searchTerms: [ ]
			}, {
				title: "fa-industry",
				searchTerms: [ "factory" ]
			}, {
				title: "fa-info",
				searchTerms: [ "help", "information", "more", "details" ]
			}, {
				title: "fa-info-circle",
				searchTerms: [ "help", "information", "more", "details" ]
			}, {
				title: "fa-instagram",
				searchTerms: [ ]
			}, {
				title: "fa-internet-explorer",
				searchTerms: [ "browser", "ie" ]
			}, {
				title: "fa-ioxhost",
				searchTerms: [ ]
			}, {
				title: "fa-italic",
				searchTerms: [ "italics" ]
			}, {
				title: "fa-joomla",
				searchTerms: [ ]
			}, {
				title: "fa-jsfiddle",
				searchTerms: [ ]
			}, {
				title: "fa-key",
				searchTerms: [ "unlock", "password" ]
			}, {
				title: "fa-keyboard-o",
				searchTerms: [ "type", "input" ]
			}, {
				title: "fa-language",
				searchTerms: [ ]
			}, {
				title: "fa-laptop",
				searchTerms: [ "demo", "computer", "device", "pc" ]
			}, {
				title: "fa-lastfm",
				searchTerms: [ ]
			}, {
				title: "fa-lastfm-square",
				searchTerms: [ ]
			}, {
				title: "fa-leaf",
				searchTerms: [ "eco", "nature", "plant" ]
			}, {
				title: "fa-leanpub",
				searchTerms: [ ]
			}, {
				title: "fa-lemon-o",
				searchTerms: [ "food" ]
			}, {
				title: "fa-level-down",
				searchTerms: [ "level-down" ]
			}, {
				title: "fa-level-up",
				searchTerms: [ "level-up" ]
			}, {
				title: "fa-support",
				searchTerms: [ "support" ]
			}, {
				title: "fa-lightbulb-o",
				searchTerms: [ "idea", "inspiration" ]
			}, {
				title: "fa-link",
				searchTerms: [ "chain" ]
			}, {
				title: "fa-linkedin-square",
				searchTerms: [ "linkedin-square" ]
			}, {
				title: "fa-linkedin",
				searchTerms: [ "linkedin" ]
			}, {
				title: "fa-linode",
				searchTerms: [ ]
			}, {
				title: "fa-linux",
				searchTerms: [ "tux" ]
			}, {
				title: "fa-try",
				searchTerms: [ "try", "turkish", "try" ]
			}, {
				title: "fa-list",
				searchTerms: [ "ul", "ol", "checklist", "finished",
					"completed", "done",
					"todo" ]
			}, {
				title: "fa-list-alt",
				searchTerms: [ "ul", "ol", "checklist", "finished",
					"completed", "done",
					"todo" ]
			}, {
				title: "fa-list-ol",
				searchTerms: [ "ul", "ol", "checklist", "list", "todo", "list",
					"numbers" ]
			}, {
				title: "fa-list-ul",
				searchTerms: [ "ul", "ol", "checklist", "todo", "list" ]
			}, {
				title: "fa-location-arrow",
				searchTerms: [ "map", "coordinates", "location", "address",
					"place",
					"where", "gps" ]
			}, {
				title: "fa-lock",
				searchTerms: [ "protect", "admin", "security" ]
			}, {
				title: "fa-long-arrow-down",
				searchTerms: [ "long-arrow-down" ]
			}, {
				title: "fa-long-arrow-left",
				searchTerms: [ "previous", "back", "long-arrow-left" ]
			}, {
				title: "fa-long-arrow-right",
				searchTerms: [ "long-arrow-right" ]
			}, {
				title: "fa-long-arrow-up",
				searchTerms: [ "long-arrow-up" ]
			}, {
				title: "fa-low-vision",
				searchTerms: [ ]
			}, {
				title: "fa-magic",
				searchTerms: [ "wizard", "automatic", "autocomplete" ]
			}, {
				title: "fa-magnet",
				searchTerms: [ ]
			}, {
				title: "fa-male",
				searchTerms: [ "man", "human", "user", "person", "profile" ]
			}, {
				title: "fa-map",
				searchTerms: [ ]
			}, {
				title: "fa-map-o",
				searchTerms: [ ]
			}, {
				title: "fa-map-marker",
				searchTerms: [ "map", "pin", "location", "coordinates",
					"localize",
					"address", "travel", "where", "place", "gps" ]
			}, {
				title: "fa-map-pin",
				searchTerms: [ ]
			}, {
				title: "fa-map-signs",
				searchTerms: [ ]
			}, {
				title: "fa-mars",
				searchTerms: [ "male" ]
			}, {
				title: "fa-mars-double",
				searchTerms: [ ]
			}, {
				title: "fa-mars-stroke",
				searchTerms: [ ]
			}, {
				title: "fa-mars-stroke-h",
				searchTerms: [ ]
			}, {
				title: "fa-mars-stroke-v",
				searchTerms: [ ]
			}, {
				title: "fa-maxcdn",
				searchTerms: [ ]
			}, {
				title: "fa-medium",
				searchTerms: [ ]
			}, {
				title: "fa-medkit",
				searchTerms: [ "first aid", "firstaid", "help", "support",
					"health" ]
			}, {
				title: "fa-meetup",
				searchTerms: [ ]
			}, {
				title: "fa-meh-o",
				searchTerms: [ "face", "emoticon", "rating", "neutral" ]
			}, {
				title: "fa-mercury",
				searchTerms: [ "transgender" ]
			}, {
				title: "fa-microchip",
				searchTerms: [ ]
			}, {
				title: "fa-microphone",
				searchTerms: [ "record", "voice", "sound" ]
			}, {
				title: "fa-microphone-slash",
				searchTerms: [ "record", "voice", "sound", "mute" ]
			}, {
				title: "fa-minus",
				searchTerms: [ "hide", "minify", "delete", "remove", "trash",
					"hide",
					"collapse" ]
			}, {
				title: "fa-minus-circle",
				searchTerms: [ "delete", "remove", "trash", "hide" ]
			}, {
				title: "fa-minus-square",
				searchTerms: [ "hide", "minify", "delete", "remove", "trash",
					"hide",
					"collapse" ]
			}, {
				title: "fa-minus-square-o",
				searchTerms: [ "hide", "minify", "delete", "remove", "trash",
					"hide",
					"collapse" ]
			}, {
				title: "fa-mixcloud",
				searchTerms: [ ]
			}, {
				title: "fa-mobile",
				searchTerms: [ "cell phone", "cellphone", "text", "call",
					"iphone",
					"number", "telephone" ]
			}, {
				title: "fa-modx",
				searchTerms: [ ]
			}, {
				title: "fa-money",
				searchTerms: [ "cash", "money", "buy", "checkout", "purchase",
					"payment",
					"price" ]
			}, {
				title: "fa-moon-o",
				searchTerms: [ "night", "darker", "contrast" ]
			}, {
				title: "fa-motorcycle",
				searchTerms: [ "vehicle", "bike" ]
			}, {
				title: "fa-mouse-pointer",
				searchTerms: [ "select" ]
			}, {
				title: "fa-music",
				searchTerms: [ "note", "sound" ]
			}, {
				title: "fa-neuter",
				searchTerms: [ ]
			}, {
				title: "fa-newspaper-o",
				searchTerms: [ "press", "article" ]
			}, {
				title: "fa-object-group",
				searchTerms: [ "design" ]
			}, {
				title: "fa-object-group",
				searchTerms: [ "design" ]
			}, {
				title: "fa-object-ungroup",
				searchTerms: [ "design" ]
			}, {
				title: "fa-object-ungroup",
				searchTerms: [ "design" ]
			}, {
				title: "fa-odnoklassniki",
				searchTerms: [ ]
			}, {
				title: "fa-odnoklassniki-square",
				searchTerms: [ ]
			}, {
				title: "fa-opencart",
				searchTerms: [ ]
			}, {
				title: "fa-openid",
				searchTerms: [ ]
			}, {
				title: "fa-opera",
				searchTerms: [ ]
			}, {
				title: "fa-optin-monster",
				searchTerms: [ ]
			}, {
				title: "fa-dedent",
				searchTerms: [ ]
			}, {
				title: "fa-pagelines",
				searchTerms: [ "leaf", "leaves", "tree", "plant", "eco",
					"nature" ]
			}, {
				title: "fa-paint-brush",
				searchTerms: [ ]
			}, {
				title: "fa-send",
				searchTerms: [ ]
			}, {
				title: "fa-send-o",
				searchTerms: [ ]
			}, {
				title: "fa-paperclip",
				searchTerms: [ "attachment" ]
			}, {
				title: "fa-paragraph",
				searchTerms: [ ]
			}, {
				title: "fa-paste",
				searchTerms: [ "copy", "clipboard" ]
			}, {
				title: "fa-pause",
				searchTerms: [ "wait" ]
			}, {
				title: "fa-pause-circle",
				searchTerms: [ ]
			}, {
				title: "fa-pause-circle-o",
				searchTerms: [ ]
			}, {
				title: "fa-paw",
				searchTerms: [ "pet" ]
			}, {
				title: "fa-paypal",
				searchTerms: [ ]
			}, {
				title: "fa-pencil-square",
				searchTerms: [ "write", "edit", "update", "pencil-square" ]
			}, {
				title: "fa-pencil-square-o",
				searchTerms: [ "write", "edit", "update", "pencil-square" ]
			}, {
				title: "fa-pencil",
				searchTerms: [ "write", "edit", "update", "pencil", "design" ]
			}, {
				title: "fa-percent",
				searchTerms: [ ]
			}, {
				title: "fa-phone",
				searchTerms: [ "call", "voice", "number", "support", "earphone",
					"telephone" ]
			}, {
				title: "fa-phone-square",
				searchTerms: [ "call", "voice", "number", "support",
					"telephone" ]
			}, {
				title: "fa-volume-control-phone",
				searchTerms: [ "telephone", "volume-control-phone" ]
			}, {
				title: "fa-pied-piper",
				searchTerms: [ ]
			}, {
				title: "fa-pied-piper-alt",
				searchTerms: [ ]
			}, {
				title: "fa-pied-piper-pp",
				searchTerms: [ ]
			}, {
				title: "fa-pinterest",
				searchTerms: [ ]
			}, {
				title: "fa-pinterest-p",
				searchTerms: [ ]
			}, {
				title: "fa-pinterest-square",
				searchTerms: [ ]
			}, {
				title: "fa-plane",
				searchTerms: [ "travel", "trip", "location", "destination",
					"airplane",
					"fly", "mode" ]
			}, {
				title: "fa-play",
				searchTerms: [ "start", "playing", "music", "sound" ]
			}, {
				title: "fa-play-circle",
				searchTerms: [ "start", "playing" ]
			}, {
				title: "fa-play-circle-o",
				searchTerms: [ "start", "playing" ]
			}, {
				title: "fa-plug",
				searchTerms: [ "power", "connect" ]
			}, {
				title: "fa-plus",
				searchTerms: [ "add", "new", "create", "expand" ]
			}, {
				title: "fa-plus-circle",
				searchTerms: [ "add", "new", "create", "expand" ]
			}, {
				title: "fa-plus-square",
				searchTerms: [ "add", "new", "create", "expand" ]
			}, {
				title: "fa-plus-square-o",
				searchTerms: [ "add", "new", "create", "expand" ]
			}, {
				title: "fa-podcast",
				searchTerms: [ ]
			}, {
				title: "fa-gbp",
				searchTerms: [ "gbp", "gbp" ]
			}, {
				title: "fa-power-off",
				searchTerms: [ "on" ]
			}, {
				title: "fa-print",
				searchTerms: [ ]
			}, {
				title: "fa-product-hunt",
				searchTerms: [ ]
			}, {
				title: "fa-puzzle-piece",
				searchTerms: [ "addon", "add-on", "section" ]
			}, {
				title: "fa-qq",
				searchTerms: [ ]
			}, {
				title: "fa-qrcode",
				searchTerms: [ "scan" ]
			}, {
				title: "fa-question",
				searchTerms: [ "help", "information", "unknown", "support" ]
			}, {
				title: "fa-question-circle",
				searchTerms: [ "help", "information", "unknown", "support" ]
			}, {
				title: "fa-question-circle-o",
				searchTerms: [ "help", "information", "unknown", "support" ]
			}, {
				title: "fa-quora",
				searchTerms: [ ]
			}, {
				title: "fa-quote-left",
				searchTerms: [ ]
			}, {
				title: "fa-quote-right",
				searchTerms: [ ]
			}, {
				title: "fa-random",
				searchTerms: [ "sort", "shuffle" ]
			}, {
				title: "fa-ravelry",
				searchTerms: [ ]
			}, {
				title: "fa-ra",
				searchTerms: [ ]
			}, {
				title: "fa-recycle",
				searchTerms: [ ]
			}, {
				title: "fa-reddit",
				searchTerms: [ ]
			}, {
				title: "fa-reddit-alien",
				searchTerms: [ ]
			}, {
				title: "fa-reddit-square",
				searchTerms: [ ]
			}, {
				title: "fa-repeat",
				searchTerms: [ "forward", "repeat", "repeat" ]
			}, {
				title: "fa-registered",
				searchTerms: [ ]
			}, {
				title: "fa-renren",
				searchTerms: [ ]
			}, {
				title: "fa-reply",
				searchTerms: [ ]
			}, {
				title: "fa-reply-all",
				searchTerms: [ ]
			}, {
				title: "fa-retweet",
				searchTerms: [ "refresh", "reload", "share", "swap" ]
			}, {
				title: "fa-road",
				searchTerms: [ "street" ]
			}, {
				title: "fa-rocket",
				searchTerms: [ "app" ]
			}, {
				title: "fa-feed",
				searchTerms: [ "feed" ]
			}, {
				title: "fa-rss-square",
				searchTerms: [ "feed", "blog" ]
			}, {
				title: "fa-rub",
				searchTerms: [ "rub", "rub" ]
			}, {
				title: "fa-inr",
				searchTerms: [ "indian", "inr" ]
			}, {
				title: "fa-save",
				searchTerms: [ "floppy", "floppy-o" ]
			}, {
				title: "fa-scribd",
				searchTerms: [ ]
			}, {
				title: "fa-search",
				searchTerms: [ "magnify", "zoom", "enlarge", "bigger" ]
			}, {
				title: "fa-search-minus",
				searchTerms: [ "magnify", "minify", "zoom", "smaller" ]
			}, {
				title: "fa-search-plus",
				searchTerms: [ "magnify", "zoom", "enlarge", "bigger" ]
			}, {
				title: "fa-eercast",
				searchTerms: [ "eercast" ]
			}, {
				title: "fa-sellsy",
				searchTerms: [ ]
			}, {
				title: "fa-server",
				searchTerms: [ ]
			}, {
				title: "fa-share",
				searchTerms: [ ]
			}, {
				title: "fa-share-alt",
				searchTerms: [ ]
			}, {
				title: "fa-share-alt-square",
				searchTerms: [ ]
			}, {
				title: "fa-share-square",
				searchTerms: [ "social", "send" ]
			}, {
				title: "fa-share-square-o",
				searchTerms: [ "social", "send" ]
			}, {
				title: "fa-shield",
				searchTerms: [ "shield" ]
			}, {
				title: "fa-ship",
				searchTerms: [ "boat", "sea" ]
			}, {
				title: "fa-shirtsinbulk",
				searchTerms: [ ]
			}, {
				title: "fa-shopping-bag",
				searchTerms: [ ]
			}, {
				title: "fa-shopping-basket",
				searchTerms: [ ]
			}, {
				title: "fa-shopping-cart",
				searchTerms: [ "checkout", "buy", "purchase", "payment" ]
			}, {
				title: "fa-shower",
				searchTerms: [ ]
			}, {
				title: "fa-sign-in",
				searchTerms: [ "enter", "join", "log in", "login", "sign up",
					"sign in",
					"signin", "signup", "arrow", "sign-in" ]
			}, {
				title: "fa-signing",
				searchTerms: [ ]
			}, {
				title: "fa-sign-out",
				searchTerms: [ "log out", "logout", "leave", "exit", "arrow",
					"sign-out" ]
			}, {
				title: "fa-signal",
				searchTerms: [ "graph", "bars", "status" ]
			}, {
				title: "fa-simplybuilt",
				searchTerms: [ ]
			}, {
				title: "fa-sitemap",
				searchTerms: [ "directory", "hierarchy", "organization" ]
			}, {
				title: "fa-skyatlas",
				searchTerms: [ ]
			}, {
				title: "fa-skype",
				searchTerms: [ ]
			}, {
				title: "fa-slack",
				searchTerms: [ "hashtag", "anchor", "hash" ]
			}, {
				title: "fa-sliders",
				searchTerms: [ "settings", "sliders" ]
			}, {
				title: "fa-slideshare",
				searchTerms: [ ]
			}, {
				title: "fa-smile-o",
				searchTerms: [ "face", "emoticon", "happy", "approve",
					"satisfied",
					"rating" ]
			}, {
				title: "fa-snapchat",
				searchTerms: [ ]
			}, {
				title: "fa-snapchat-ghost",
				searchTerms: [ ]
			}, {
				title: "fa-snapchat-square",
				searchTerms: [ ]
			}, {
				title: "fa-snowflake-o",
				searchTerms: [ ]
			}, {
				title: "fa-sort",
				searchTerms: [ "order" ]
			}, {
				title: "fa-sort-alpha-asc",
				searchTerms: [ "sort-alpha-asc" ]
			}, {
				title: "fa-sort-alpha-desc",
				searchTerms: [ "sort-alpha-desc" ]
			}, {
				title: "fa-sort-amount-asc",
				searchTerms: [ "sort-amount-asc" ]
			}, {
				title: "fa-sort-amount-desc",
				searchTerms: [ "sort-amount-desc" ]
			}, {
				title: "fa-sort-desc",
				searchTerms: [ "arrow", "descending", "sort-desc" ]
			}, {
				title: "fa-sort-numeric-asc",
				searchTerms: [ "numbers", "sort-numeric-asc" ]
			}, {
				title: "fa-sort-numeric-desc",
				searchTerms: [ "numbers", "sort-numeric-desc" ]
			}, {
				title: "fa-sort-asc",
				searchTerms: [ "arrow", "ascending", "sort-asc" ]
			}, {
				title: "fa-soundcloud",
				searchTerms: [ ]
			}, {
				title: "fa-space-shuttle",
				searchTerms: [ ]
			}, {
				title: "fa-spinner",
				searchTerms: [ "loading", "progress" ]
			}, {
				title: "fa-spotify",
				searchTerms: [ ]
			}, {
				title: "fa-square",
				searchTerms: [ "block", "box" ]
			}, {
				title: "fa-square-o",
				searchTerms: [ "block", "box" ]
			}, {
				title: "fa-stack-exchange",
				searchTerms: [ ]
			}, {
				title: "fa-stack-overflow",
				searchTerms: [ ]
			}, {
				title: "fa-star",
				searchTerms: [ "award", "achievement", "night", "rating",
					"score",
					"favorite" ]
			}, {
				title: "fa-star-o",
				searchTerms: [ "award", "achievement", "night", "rating",
					"score",
					"favorite" ]
			}, {
				title: "fa-star-half",
				searchTerms: [ "award", "achievement", "rating", "score",
					"star-half-empty", "star-half-full" ]
			}, {
				title: "fa-star-half-o",
				searchTerms: [ "award", "achievement", "rating", "score",
					"star-half-empty", "star-half-full" ]
			}, {
				title: "fa-steam",
				searchTerms: [ ]
			}, {
				title: "fa-steam-square",
				searchTerms: [ ]
			}, {
				title: "fa-step-backward",
				searchTerms: [ "rewind", "previous", "beginning", "start",
					"first" ]
			}, {
				title: "fa-step-forward",
				searchTerms: [ "next", "end", "last" ]
			}, {
				title: "fa-stethoscope",
				searchTerms: [ ]
			}, {
				title: "fa-sticky-note",
				searchTerms: [ ]
			}, {
				title: "fa-sticky-note-o",
				searchTerms: [ ]
			}, {
				title: "fa-stop",
				searchTerms: [ "block", "box", "square" ]
			}, {
				title: "fa-stop-circle",
				searchTerms: [ ]
			}, {
				title: "fa-stop-circle-o",
				searchTerms: [ ]
			}, {
				title: "fa-street-view",
				searchTerms: [ "map" ]
			}, {
				title: "fa-strikethrough",
				searchTerms: [ ]
			}, {
				title: "fa-stumbleupon",
				searchTerms: [ ]
			}, {
				title: "fa-stumbleupon-circle",
				searchTerms: [ ]
			}, {
				title: "fa-subscript",
				searchTerms: [ ]
			}, {
				title: "fa-subway",
				searchTerms: [ ]
			}, {
				title: "fa-suitcase",
				searchTerms: [ "trip", "luggage", "travel", "move", "baggage" ]
			}, {
				title: "fa-sun-o",
				searchTerms: [ "weather", "contrast", "lighter", "brighten",
					"day" ]
			}, {
				title: "fa-superpowers",
				searchTerms: [ ]
			}, {
				title: "fa-superscript",
				searchTerms: [ "exponential" ]
			}, {
				title: "fa-refresh",
				searchTerms: [ "reload", "refresh", "refresh" ]
			}, {
				title: "fa-table",
				searchTerms: [ "data", "excel", "spreadsheet" ]
			}, {
				title: "fa-tablet",
				searchTerms: [ "ipad", "device" ]
			}, {
				title: "fa-tachometer",
				searchTerms: [ "tachometer", "dashboard" ]
			}, {
				title: "fa-dashboard",
				searchTerms: [ "tachometer", "dashboard" ]
			}, {
				title: "fa-tag",
				searchTerms: [ "label" ]
			}, {
				title: "fa-tags",
				searchTerms: [ "labels" ]
			}, {
				title: "fa-tasks",
				searchTerms: [ "progress", "loading", "downloading",
					"downloads",
					"settings" ]
			}, {
				title: "fa-telegram",
				searchTerms: [ ]
			}, {
				title: "fa-tencent-weibo",
				searchTerms: [ ]
			}, {
				title: "fa-terminal",
				searchTerms: [ "command", "prompt", "code" ]
			}, {
				title: "fa-text-height",
				searchTerms: [ ]
			}, {
				title: "fa-text-width",
				searchTerms: [ ]
			}, {
				title: "fa-th",
				searchTerms: [ "blocks", "squares", "boxes", "grid" ]
			}, {
				title: "fa-th-large",
				searchTerms: [ "blocks", "squares", "boxes", "grid" ]
			}, {
				title: "fa-th-list",
				searchTerms: [ "ul", "ol", "checklist", "finished",
					"completed", "done",
					"todo" ]
			}, {
				title: "fa-themeisle",
				searchTerms: [ ]
			}, {
				title: "fa-thermometer",
				searchTerms: [ "temperature", "fever" ]
			}, {
				title: "fa-thermometer-0",
				searchTerms: [ "status" ]
			}, {
				title: "fa-thermometer-4",
				searchTerms: [ "status" ]
			}, {
				title: "fa-thermometer-2",
				searchTerms: [ "status" ]
			}, {
				title: "fa-thermometer-1",
				searchTerms: [ "status" ]
			}, {
				title: "fa-thermometer-3",
				searchTerms: [ "status" ]
			}, {
				title: "fa-thumbs-down",
				searchTerms: [ "dislike", "disapprove", "disagree", "hand", "thumbs-o-down"
				]
			}, {
				title: "fa-thumbs-o-down",
				searchTerms: [ "dislike", "disapprove", "disagree", "hand", "thumbs-o-down"
				]
			}, {
				title: "fa-thumbs-up",
				searchTerms: [ "like", "favorite", "approve", "agree", "hand",
					"thumbs-o-up" ]
			}, {
				title: "fa-thumbs-o-up",
				searchTerms: [ "like", "favorite", "approve", "agree", "hand",
					"thumbs-o-up" ]
			}, {
				title: "fa-thumb-tack",
				searchTerms: [ "marker", "pin", "location", "coordinates",
					"thumb-tack" ]
			}, {
				title: "fa-ticket",
				searchTerms: [ "ticket" ]
			}, {
				title: "fa-times",
				searchTerms: [ "close", "exit", "x" ]
			}, {
				title: "fa-times-circle",
				searchTerms: [ "close", "exit", "x" ]
			}, {
				title: "fa-times-circle-o",
				searchTerms: [ "close", "exit", "x" ]
			}, {
				title: "fa-tint",
				searchTerms: [ "raindrop", "waterdrop", "drop", "droplet" ]
			}, {
				title: "fa-toggle-off",
				searchTerms: [ "switch" ]
			}, {
				title: "fa-toggle-on",
				searchTerms: [ "switch" ]
			}, {
				title: "fa-trademark",
				searchTerms: [ ]
			}, {
				title: "fa-train",
				searchTerms: [ ]
			}, {
				title: "fa-intersex",
				searchTerms: [ "intersex" ]
			}, {
				title: "fa-transgender-alt",
				searchTerms: [ ]
			}, {
				title: "fa-trash",
				searchTerms: [ "garbage", "delete", "remove", "hide" ]
			}, {
				title: "fa-trash-o",
				searchTerms: [ "garbage", "delete", "remove", "hide", "trash",
					"trash-o" ]
			}, {
				title: "fa-tree",
				searchTerms: [ ]
			}, {
				title: "fa-trello",
				searchTerms: [ ]
			}, {
				title: "fa-tripadvisor",
				searchTerms: [ ]
			}, {
				title: "fa-trophy",
				searchTerms: [ "award", "achievement", "cup", "winner", "game" ]
			}, {
				title: "fa-truck",
				searchTerms: [ "shipping" ]
			}, {
				title: "fa-tty",
				searchTerms: [ ]
			}, {
				title: "fa-tumblr",
				searchTerms: [ ]
			}, {
				title: "fa-tumblr-square",
				searchTerms: [ ]
			}, {
				title: "fa-television",
				searchTerms: [ "display", "computer", "monitor", "television" ]
			}, {
				title: "fa-twitch",
				searchTerms: [ ]
			}, {
				title: "fa-twitter",
				searchTerms: [ "tweet", "social network" ]
			}, {
				title: "fa-twitter-square",
				searchTerms: [ "tweet", "social network" ]
			}, {
				title: "fa-umbrella",
				searchTerms: [ ]
			}, {
				title: "fa-underline",
				searchTerms: [ ]
			}, {
				title: "fa-universal-access",
				searchTerms: [ ]
			}, {
				title: "fa-bank",
				searchTerms: [ "bank", "institution" ]
			}, {
				title: "fa-unlink",
				searchTerms: [ "remove", "chain", "chain-broken" ]
			}, {
				title: "fa-unlock",
				searchTerms: [ "protect", "admin", "password", "lock" ]
			}, {
				title: "fa-unlock-alt",
				searchTerms: [ "protect", "admin", "password", "lock" ]
			}, {
				title: "fa-upload",
				searchTerms: [ "import" ]
			}, {
				title: "fa-usb",
				searchTerms: [ ]
			}, {
				title: "fa-user",
				searchTerms: [ "person", "man", "head", "profile", "account" ]
			}, {
				title: "fa-user-o",
				searchTerms: [ "person", "man", "head", "profile", "account" ]
			}, {
				title: "fa-user-circle",
				searchTerms: [ "person", "man", "head", "profile", "account" ]
			}, {
				title: "fa-user-circle-o",
				searchTerms: [ "person", "man", "head", "profile", "account" ]
			}, {
				title: "fa-user-md",
				searchTerms: [ "doctor", "profile", "medical", "nurse", "job", "occupation"
				]
			}, {
				title: "fa-user-plus",
				searchTerms: [ "sign up", "signup" ]
			}, {
				title: "fa-user-secret",
				searchTerms: [ "whisper", "spy", "incognito", "privacy" ]
			}, {
				title: "fa-user-times",
				searchTerms: [ ]
			}, {
				title: "fa-users",
				searchTerms: [ "people", "profiles", "persons" ]
			}, {
				title: "fa-spoon",
				searchTerms: [ "spoon" ]
			}, {
				title: "fa-cutlery",
				searchTerms: [ "food", "restaurant", "spoon", "knife",
					"dinner", "eat",
					"cutlery" ]
			}, {
				title: "fa-venus",
				searchTerms: [ "female" ]
			}, {
				title: "fa-venus-double",
				searchTerms: [ ]
			}, {
				title: "fa-venus-mars",
				searchTerms: [ ]
			}, {
				title: "fa-viacoin",
				searchTerms: [ ]
			}, {
				title: "fa-viadeo",
				searchTerms: [ ]
			}, {
				title: "fa-viadeo-square",
				searchTerms: [ ]
			}, {
				title: "fa-video-camera",
				searchTerms: [ "film", "movie", "record", "camera",
					"video-camera" ]
			}, {
				title: "fa-vimeo",
				searchTerms: [ ]
			}, {
				title: "fa-vimeo-square",
				searchTerms: [ ]
			}, {
				title: "fa-vine",
				searchTerms: [ ]
			}, {
				title: "fa-vk",
				searchTerms: [ ]
			}, {
				title: "fa-volume-down",
				searchTerms: [ "audio", "lower", "quieter", "sound", "music" ]
			}, {
				title: "fa-volume-off",
				searchTerms: [ "audio", "mute", "sound", "music" ]
			}, {
				title: "fa-volume-up",
				searchTerms: [ "audio", "higher", "louder", "sound", "music" ]
			}, {
				title: "fa-weibo",
				searchTerms: [ ]
			}, {
				title: "fa-wechat",
				searchTerms: [ ]
			}, {
				title: "fa-whatsapp-square",
				searchTerms: [ ]
			}, {
				title: "fa-wheelchair",
				searchTerms: [ "handicap", "person" ]
			}, {
				title: "fa-wifi",
				searchTerms: [ ]
			}, {
				title: "fa-wikipedia-w",
				searchTerms: [ ]
			}, {
				title: "fa-window-close",
				searchTerms: [ ]
			}, {
				title: "fa-window-close-o",
				searchTerms: [ ]
			}, {
				title: "fa-window-maximize",
				searchTerms: [ ]
			}, {
				title: "fa-window-minimize",
				searchTerms: [ ]
			}, {
				title: "fa-window-restore",
				searchTerms: [ ]
			}, {
				title: "fa-windows",
				searchTerms: [ "microsoft" ]
			}, {
				title: "fa-krw",
				searchTerms: [ "krw", "krw" ]
			}, {
				title: "fa-wordpress",
				searchTerms: [ ]
			}, {
				title: "fa-wpbeginner",
				searchTerms: [ ]
			}, {
				title: "fa-wpexplorer",
				searchTerms: [ ]
			}, {
				title: "fa-wpforms",
				searchTerms: [ ]
			}, {
				title: "fa-wrench",
				searchTerms: [ "settings", "fix", "update", "spanner", "tool" ]
			}, {
				title: "fa-xing",
				searchTerms: [ ]
			}, {
				title: "fa-xing-square",
				searchTerms: [ ]
			}, {
				title: "fa-yc",
				searchTerms: [ ]
			}, {
				title: "fa-yahoo",
				searchTerms: [ ]
			}, {
				title: "fa-yelp",
				searchTerms: [ ]
			}, {
				title: "fa-cny",
				searchTerms: [ "jpy", "jpy" ]
			}, {
				title: "fa-yoast",
				searchTerms: [ ]
			}, {
				title: "fa-youtube",
				searchTerms: [ "video", "film", "youtube-play",
					"youtube-square" ]
			}, {
				title: "fa-youtube-square",
				searchTerms: [ ]
			} ]
	} );
} );