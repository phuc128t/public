# Create a patch using diff
## Create a patch for a file
#### Create patch
Let’s say you have an original file. You make some changes in it and save the result to a new updated file.

> diff -Naur OriginalPathFile ChangedPathFile > Filename.patch


*Note: we should run diff command above in root folder*

#### Apply a patch file

> patch -p0 < Filename.patch

## Create a patch for whole folder
> diff -crB dir_original dir_updated > Filename.patch
