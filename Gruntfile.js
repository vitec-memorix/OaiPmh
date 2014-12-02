module.exports = function (grunt) {
    "use strict";

    var directoriesConfig = {
        composer   : 'vendor',
        composerBin: 'vendor/bin',
        reports    : 'reports',
        php        : './src'
    };

    // Project configuration.
    grunt.initConfig({
        pkg        : grunt.file.readJSON('package.json'),
        directories: directoriesConfig,
        clean      : {
            report: {
                src: ["<%= directories.reports %>"]
            }
        },

        mkdir: {
            report: {
                options: {
                    create: ['<%= directories.reports %>']
                }
            }
        },

        phplint: {
            lint: ["<%= directories.php %>/**/*.php"]
        },

        phpcs: {
            application: {
                dir: ['<%= directories.php %>']
            },
            options    : {
                standard      : 'PSR2',
                reportFile    : '<%= directories.reports %>/checkstyle.xml',
                ignoreExitCode: true,
                report        : 'checkstyle'
            }
        },

        phpmd: {
            bin: 'phpmd',
            application: {
                dir: '<%= directories.php %>'
            },
            options    : {
                rulesets  : 'unusedcode,design,codesize,naming',
                reportFile: '<%= directories.reports %>/pmd.xml'
            }
        },

        phpunit: {
            classes: {
            },
            options: {
                coverageClover: '<%= directories.reports %>/clover.xml',
                logJunit      : '<%= directories.reports %>/junit.xml',
                colors        : true
            }
        }
    });
    // Load phpunit
    grunt.loadNpmTasks('grunt-phpunit');

    // load phpcs
    grunt.loadNpmTasks('grunt-phpcs');

    // Load phpmd
    grunt.loadNpmTasks('grunt-phpmd');

    // Load the plugin that provides the phplint task
    grunt.loadNpmTasks("grunt-phplint");

    // Load mkdir
    grunt.loadNpmTasks('grunt-mkdir');

    // Load clean
    grunt.loadNpmTasks('grunt-contrib-clean');

    // Default task(s).
    grunt.registerTask('default', ['clean:report', 'mkdir:report', 'phplint', 'phpcs', 'phpunit']);
};