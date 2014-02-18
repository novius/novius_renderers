Allow to add several medias on a model.
A key must be provided and will be concatenate with an index.

Thus, reading all the corresponding medias can be done by doing the same thing as in inputs.view :
$index = 1;
while(!empty($item->medias->{'key'.$index})) {
    $media = $item->medias->{'key'.$index};
    $index++;
}

See config.sample to know how to deal with the save() of the medias in a crud.