Create a patch using diff
Let’s say you have an original file.You make some changes in it and save the result to a new updated file.


diff -Naur OriginalPathFile ChangedPathFile > Filename.patch


Note: we should run diff command above in root folder

Apply a patch file

patch -p0 < Filename.patch


dasd
