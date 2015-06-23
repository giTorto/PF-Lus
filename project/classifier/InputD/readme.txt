README

- Data is already split into train & test
Everything that contains 'train' is from training, 'test' for testing

- Files
*.data 
	token-per-line format with tokens & concept tags for sequence labeling
*.feats.txt
	POS-tag and Lemmas in token-per-line format (with tokens on first column) can be used both for sequence labeling and classification
*.tok
	utterance per line format for classification
*.utt.labels.txt
	labels for utterance per line format