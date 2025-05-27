## Create a patch using phpStorm
Local History > Show History ...

## Create a patch using "git diff"
#### Create patch
Let’s say you have an original file. You make some changes in it and save the result to a new updated file.

> git diff --no-index OriginalPathFile ChangedPathFile > Filename.patch


*Note: we should run diff command above in root folder*

#### Apply a patch file

> patch -p1 < Filename.patch
<br/>

## Create a patch using "diff"
#### Create patch
Let’s say you have an original file. You make some changes in it and save the result to a new updated file.

> diff -Naur OriginalPathFile ChangedPathFile > Filename.patch


*Note: we should run diff command above in root folder*

#### Apply a patch file

> patch -p0 < Filename.patch
