# Setup #
1. Install using install/index.php.
2. Setup dynamicPages/common/setUpEnvironment.php.  dynamicPages/common/setUpEnvironment-example.php can be used as an example.

# Git Setup #
1. If using Eclipse, create a chuckanutbay.com project.  If not, mdkir chuckanutbay.com.
<pre>
cd chuckanutbay.com
git init
git remote add github git@github.com:BigLep/chuckanutbay.com.git
git fetch github
git checkout master
</pre>


# Code Reviews #
1. Install Git: http://github.com/guides/setting-up-a-remote-repository-using-github-and-osx
2. Download upload.py from: http://codereview.loeppky.com/static/upload.py 
3. chmod +x upload.py
4. upload.py -s codereview.loeppky.com --rev=HEAD

# Eclipse Setup #
* Install PHP Development Tools (http://www.eclipse.org/pdt) (see http://wiki.eclipse.org/PDT/Installation#From_Update_Site)
* Instal Markdown plugin (http://www.winterwell.com/software/markdown-editor.php)

# MAMP Setup #
* Download MAMP: http://www.mamp.info/en/index.html
* Symlink chuckanutbay.com directory into MAMP
<pre>
# Set these variables first
projectDir=~/Documents/workspace/chuckanutbay.com
mampRootDir=/Applications/MAMP/htdocs
# Run this command
ln -s $projectDir $mampRootDir/
</pre>

# Error Logs #
Error logs can be viewed at: http://loeppky.com:2082/frontend/x3/stats/errlog.html

# Markdown Syntax #
This file should be modified using Markdown syntax.  You can learn more about it here: http://daringfireball.net/projects/markdown/syntax

# Git Help #
Making files executable: http://www.kernel.org/pub/software/scm/git/docs/git-update-index.html
