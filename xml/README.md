The method for DAO generation is taken from [Sepa Extension](https://github.com/Project60/org.project60.sepa). Thanks a lot Xavier for the suggestion. 

You need to symlink `xml/schema/Civisocial` and `xml/Civisocial.xml` to your civicrm root xml folder. This can be done by

```
cd <civicrm_root>/xml/schema
ln -s <extesion_root>/xml/schema/Civisocial Civisocial
ln -s <extension_root>/xml/schema/Civisocial.xml Civisocial.xml
```

You further need to add the following line to xml/schema/schema.xml

`<xi:include href="Civisocial/files.xml"          parse="xml" />`

Then running `xml/GenCode.php` will get to the DAO file in `<civicrm_root>/CRM/Civisocial/DAO`.


