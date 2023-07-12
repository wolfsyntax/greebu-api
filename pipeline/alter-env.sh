file=".env"
while IFS= read line
do
    secretValue=$(cut -d "=" -f2- <<< "$line")
    keyName=$(cut -d "=" -f 1 <<< "$line")

    if [[ $secretValue =~ "_PIPELINE" ]]; then
        eval varValue='$'${keyName}
       sed -i  "s|$secretValue|$varValue|g" "$file"
    fi
    echo "$secretValue"
done <"$file"
