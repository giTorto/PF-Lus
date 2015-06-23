import sys
from pred_utilities import predict, evaluate, giveMeMeanOfClassification, giveMeVariance
from utilities import labels_file_to_set, set_up_labels_prob, set_up_stop_words, compute_max_and_mean
import json

__author__ = 'gt'


inputVal = sys.argv[1]
output_file = sys.argv[2]
test_label = sys.argv[3]
input_string = sys.argv[4]
home_path = sys.argv[5]
labels = labels_file_to_set(home_path + "NB/labels.count")
labels_prob = set_up_labels_prob(home_path + "NB/labels.prob")
stop_words = []

ordered_labels = list(sorted(labels))
lines = []
index = 0

#max_and_mean_values = compute_max_and_mean(home_path + "NB/word_bigrams_label_table.prob")
#print(max_and_mean_values)
if input_string != "":
    lines.append(input_string)
else:
    with open(inputVal, "r") as f:
        for line in f:
            lines.append(line.strip())

classe = ""
possible_classes = {}
with open(home_path + "NB/file.pred", "w") as out:
    for line in lines:
        possible_classes = predict(line.strip(), ordered_labels, labels,  labels_prob, home_path, stop_words)
        classe = possible_classes.get("main_class")
        out.write(classe + "\n")
        index += 1
        #print("Predicted " + str(index) + " on " + str(len(lines)))

json_file = {}
json_file["concept"] = []
json_file["pos"] = []
json_file["class"] = classe
json_file["secondary_class"] = possible_classes.get("other_classes")
json_file["words"] = []
json_file["conc_conf"] = []

original_words = input_string.split(" ")
original_words.reverse()
with open(home_path + "NB/sentence_final.txt", "r") as sen_fin:
    for line in sen_fin:
        line = line.strip()
        if line != "":
            words = line.split("\t")

            if len(words) < 2:
                continue

            concept = words[2].split("/")
            json_file["words"].append(original_words.pop())
            json_file["concept"].append(concept[0])
            json_file["conc_conf"].append(concept[1])
            json_file["pos"].append(words[1])

json_obj = json.dumps(json_file)

print(json_obj)

#evaluate("NB/file.pred", test_label, inputVal, output_file)
#giveMeMeanOfClassification()
#giveMeVariance()
