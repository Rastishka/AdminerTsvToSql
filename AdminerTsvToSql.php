<?php


class AdminerTsvToSql
{
    function head() {
        if (!isset($_GET['sql'])) {
            return;
        }
        ?>
        <script type="text/javascript" nonce="<?= get_nonce() ?>">
            document.addEventListener("DOMContentLoaded", function (event) {
                var sqlarea = document.getElementsByClassName("sqlarea")[0];

                sqlarea.addEventListener('paste', function (e) {
                    var clipboardData, pastedText;
                    clipboardData = e.clipboardData || window.clipboardData;
                    pastedText = clipboardData.getData('Text');

                    var n = pastedText.search("\t");
                    var lineCount = (pastedText.match(/\r\n/g) || []).length;
                    var tabCount = (pastedText.match(/\t/g) || []).length;

                    if ((lineCount >= 1) &&
                        (tabCount >= 2)) {
                        var lines = pastedText.split("\r\n");

                        // check if valid
                        var headerColumnsLength = lines[0].split("\t").length;
                        for (var i = 1; i < lines.length; i++) {
                            if (lines[i] !== '') {
                                if (headerColumnsLength !== lines[i].split("\t").length) {
                                    return;
                                }
                            }
                        }

                        if (confirm('Convert clipboard TSV to SQL query?')) {
                            var sqlData = [];

                            for (var j = 1; j < lines.length; j++) {
                                if (lines[j] !== '') {
                                    sqlData.push(
                                        "('" +
                                        lines[j]
                                            .split("\t")
                                            .map(
                                                function (s) {
                                                    if ((s.length >= 2) &&
                                                        (s.charAt(0) === '"') &&
                                                        (s.charAt(s.length - 1) === '"')) {
                                                        s = s.substring(1, s.length - 1);
                                                    }

                                                    return s.replace(/[\0\x08\x09\x1a\n\r"'\\\%]/g, function (char) {
                                                        switch (char) {
                                                            case "\0":
                                                                return "\\0";
                                                            case "\x08":
                                                                return "\\b";
                                                            case "\x09":
                                                                return "\\t";
                                                            case "\x1a":
                                                                return "\\z";
                                                            case "\n":
                                                                return "\\n";
                                                            case "\r":
                                                                return "\\r";
                                                            case "\"":
                                                            case "'":
                                                            case "\\":
                                                            case "%":
                                                                return "\\" + char; // prepends a backslash to backslash, percent,
                                                                                    // and double/single quotes
                                                        }
                                                    });
                                                }
                                            )
                                            .join("', '") +
                                        "')"
                                    );
                                }
                            }

                            var sql =
                                'INSERT INTO `INPUT_TABLE_NAME` \n' +
                                '   (`' +
                                lines[0]
                                    .split("\t")
                                    .map(
                                        function (s) {
                                            return s.trim()
                                        }
                                    )
                                    .join('`, `') +
                                '`)\n' +
                                'VALUES \n' +
                                '   ' + sqlData.join(',\n   ') +
                                ';';

                            setTimeout(function () {
                                sqlarea.innerHTML = '';
                                document.execCommand("insertText", false, sql);

                                var eventClick = new Event('keyup');
                                sqlarea.dispatchEvent(eventClick);
                            }, 1);
                        }

                    }
                }, false);
            });
        </script>
        <?php
    }
}
