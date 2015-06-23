# PF-Lus

## Description
This project aims to create a Language Understanding System on the movie domain.

## How To Make it working
- This project is based on the classifier, provided inside the folder. The classifier is slightly different from the one given for the first assignment, since I change it slightly in order to retrieve confidences.
  - Once the project is downloaded the Naive Bayes Classifier is already trained and ready to predict
- The project is based on a Concept Tagger build with CRF++.
  - The **concept tagger must be trained** and **placed** in the path **'project/classifier/'** with the name **'crf.lm'**, otherwise the system will not work.
- In order to be fully working the project must be available at **http://localhost/project/index.html** .
- To be sure that the system works properly go to **http://localhost/project/test.php**, if each test return OK, the system is ready to run.

