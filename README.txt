moodle-filter_graphtable
=================

This plugins is a filter to convert data in HTML tables on a Moodle
page into a graph. Tables are created using gnuplot which needs to be
installed on the server.  If utilizes the local_math image generator
to handle generation and caching of images. It scans texts to find
tables. Graph types are determined from table caption. The local_math
plugin is used to cache date, and generate appropriate tags and images
to insert for each table. This base version creates a histogram, but
changing the gnuplot settings can easily adapt it to other types of plots.

All original files are copyright 2014 onward Daniel Thies dthies@ccal.edu
and are licensed under the included GPL 3.
