The script are developed in python 3.4.3 and uses numpy3
In order to execute the sequence labelling, there are two main script:
- createFST.py, which initialize files in order to have the basic FST
    - this script requires only one parameter:
        - the path of the train file, e.g. "InputData/NLSPARQL.train.feats.txt"
- text2fsa.py, which allows to perform prediction on sequence labelling, it is based on "createFST.py"
    - this script requires 3 parameters(the order matter):
        - the path of the file to test, e.g. "TestD/NLSPARQL.test.feats.txt"
        - the type of the input
            - if this parameter is equal to "col", this means that the first parameter received
              is used as a file, so every line is load in memory and the test phase is performed
            - otherwise, the first parameters is considered a string on which predict the labels.
              So, the input should be something like "Star of thor" if the second parameter is different from "col"
        - the third parameter is the path on which the prediction output will be stored, e.g. "fst_results.txt"

- the third script allow to evaluate the performance of the prediction
    - it requires 3 parameters, the order matter:
        - the first parameter is the test file, containing the correct POS tags for each word
                e.g. "TestD/NLSPARQL.test.feats.txt"
        - the second parameter is the desired path of the output, e.g. "fst_results.txt"
        - the third parameter is the path of the evaluator, e.g. "eval/conlleval.pl"

In order to perform the text classification, there are two main script:
- bayesLearn.py, which creates the probability for each known word with and the probability to belong to each class
    - it requires 5 parameters, the order matter:
        - the first one is a string, based on this strings, the sample with more than one label are duplicated
          with single label.
          - In other words if there is a sample with two label, then there will be two samples,
            the first sample with the first label and the second sample with second label
          - if the parameter is equal to the string "True", string are duplicated, otherwise not
        - the second one is the train file without labels, e.g. "InputD/NLSPARQL.train.tok"
        - the third one is the label train file, e.g. "InputD/NLSPARQL.train.utt.labels.txt"
        - the fourth one is the train file used to train the POS tagger, e.g. "InputD/NLSPARQL.train.feats.txt"
            - this is needed because the text classifier is based on the POS tagger. So if the "third.fst" file
              is not in the same folder, a new FST will be trained
        - the fifth one is where the train file for the concept tagger, e.g. "InputD/NLSPARQL.train.data"
            - the reason is the same as above, the text classifier depends on the concept tagger.
              So the crf.lm must be in the same classpath
        - the sixth one is the path of the concept tagger template, e.g. "CRF/crf.template"

- giveMeTheProb.py, which requires 3 parameter, please use always absolute path:
    - the first parameter is the path of the test set file, e.g. "TestD/NLSPARQL.test.txt"
    - the second parameter is the desired path of the performance results, e.g.  "NB/result.res"
    - the third parameter is the path of the test labels, e.g. "TestD/NLSPARQL.test.utt.labels.txt"
        - the test labels are used only to evaluate the performance of the prediction
    - the fourth parameter is the sentence on which perform the prediction
    - the fifth parameter is the absolute path of the classifier

- the predicted labels can be found at the path "NB/file.pred"


the scripts are based on some external script which contains all the function, like:
    - utilities.py
    - pred_utilities.py
    - fst_utilities.py