#!/bin/bash
echo "Enter commit comment: "
read $commit
git config --global user.email "unraidsquid@gmail.com"
git config --global user.name "Andrew Z"
git add *
git commit -m '$commit'
git push -f
