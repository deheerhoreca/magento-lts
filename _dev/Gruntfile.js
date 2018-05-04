module.exports = function(grunt) {
    var project = {
        scssDir: 'scss',
        scss: 'scss/main.scss',
        appDir: '../app/design/frontend/aw_mobile3/default/',
        css: '../skin/frontend/aw_mobile3/default/css/main.css',
        img: '../skin/frontend/aw_mobile3/default/images',
        imgSvgSrc: 'img/svg-src',
        js: '../skin/frontend/aw_mobile3/default/js'
    };

    grunt.initConfig({

        project: project,
        usersettings: grunt.file.exists('usersettings.json') ? grunt.file.readJSON('usersettings.json') : {},

        sass: {
            options: {
                sourceMap: false,
                outputStyle: 'expanded'
            },
            dev: {
                files: {
                    '<%= project.css %>': '<%= project.scss %>'
                }
            },
            debug: {
                files: {
                    '<%= project.css %>': '<%= project.scss %>'
                },
                options: {
                    sourceMap: true
                }
            },
            dist: {
                files: {
                    '<%= project.css %>': '<%= project.scss %>'
                },
                options: {
                    outputStyle: 'expanded'
                }
            }
        },

        postcss: {
            options: {
                map: false,
                processors: [
                    require('autoprefixer')({
                        browsers: ['last 2 versions', '> 1%', 'iOS >= 7', 'Android >= 4', 'ExplorerMobile >= 10']
                    })
                ]
            },
            default: {
                src: '<%= project.css %>'
            },
            debug: {
                src: '<%= project.css %>',
                options: {
                    map: true
                }
            }
        },

        jscs: {
            default: {
                src: [
                    '<%= project.js %>/scripts.js',
                    'Gruntfile.js'
                ]
            }
        },

        svgmin: {
            options: {
                plugins: []
            },
            dist: {
                files: [{
                    expand: true,
                    cwd: '<%= project.imgSvgSrc %>',
                    src: ['*.svg'],
                    dest: 'img/svg-optimized'
                }]
            }
        },

        svgstore: {
            options: {
                prefix: 'icon-',
                svg: {
                    style: 'display: none;'
                }
            },
            dev: {
                files: {
                    '<%= project.img %>/sprite.svg': ['img/svg-optimized/*.svg'],
                },
                options: {
                    formatting: {
                        indent_char: '	',
                        indent_size: 1
                    },
                    includedemo: '<!doctype html><html><head><style>body{background: #eee;}svg{width:50px; height:50px; fill:black;}</style><head><body>\n{{{svg}}}\n\n{{#each icons}}<svg class="svg-icon"><use xlink:href="#{{name}}" /></svg>\n{{/each}}\n\n\n</body></html>\n'
                }
            },
            dist: {
                files: {
                    '<%= project.img %>/sprite.svg': ['img/svg-optimized/*.svg'],
                }
            },
        },

        clean: {
            images: ['img/svg-optimized'],
            options: {
                force: true
            }
        },

        browserSync: {
            bsFiles: {
                src: [
                    project.appDir + '**/*.phtml',
                    project.appDir + '**/*.xml',
                    project.js + '/*.js',
                    project.img + '/**/*.{png,jpg,gif,svg}',
                ]
            },
            options: {
                proxy: '<%= usersettings.proxy %>',
                watchTask: true,
                notify: false,
                online: false,
                ghostMode: {
                    scroll: false
                }
            }
        },

        bsReload: {
            css: {
                reload: project.css
            }
        },

        watch: {
            options: {
                spawn: false
            },
            sass: {
                files: ['<%= project.scssDir %>/**/*.scss'],
                tasks: ['sass:dev', 'postcss:default', 'bsReload:css'],
            },
            images: {
                files: ['<%= project.imgSvgSrc %>/*.svg'],
                tasks: ['updateimages:dev']
            }
        }

    });

    require('load-grunt-tasks')(grunt);

    grunt.registerTask('default', ['sass:dev', 'postcss:default', 'updateimages:dev', 'browserSync', 'watch']);
    grunt.registerTask('debug', ['sass:debug', 'postcss:debug', 'updateimages:dev', 'browserSync', 'watch']);
    grunt.registerTask('build', ['sass:dist', 'postcss:default', 'updateimages:dist']);
    grunt.registerTask('updateimages:dev', ['clean:images', 'svgmin', 'svgstore:dev']);
    grunt.registerTask('updateimages:dist', ['clean:images', 'svgmin', 'svgstore:dist']);
    grunt.registerTask('test', ['jscs']);
};
