SKETCHUP_CONSOLE.clear

$file_path = "/Users/sin/Sort/Dev_Web/2017-08-16_AutoAPA/data/geometry.json"
$file = File.open($file_path, 'a')
$file.truncate(0)

$impression_layer = "Impressions"
$frame_layer = "cadres"

def puts_entity(entity, transforms, curve_processed)
  case entity.typename
    when "ComponentInstance" , "Group"
      puts_component(entity, transforms)
    when "Edge"
      curve_processed = puts_edge(entity, transforms, curve_processed)
    when "Face"
      puts_face(entity, transforms)
    when "Text"
      puts_text(entity, transforms)
    when "DimensionLinear"
      puts_dimension_linear(entity, transforms)
    when "Image"
      puts_image(entity, transforms)
  end
  return curve_processed
end

def puts_component(component, transforms)
  new_transforms = transforms * component.transformation
  curve_processed = []
  component.definition.entities.each do |entity|
    curve_processed = puts_entity(entity, new_transforms, curve_processed)
  end
end

def puts_edge(edge, transforms, curve_processed)
  if edge.curve == nil || edge.curve.is_polygon?
    output = "{ \"type\": \"edge\", "
    output << "\"start\": #{get_coord(edge.start.position, transforms)}, "
    output << "\"end\": #{get_coord(edge.end.position, transforms)} }, "
    $file.puts output
  elsif !(curve_processed.include? edge)
    curve_processed.push(edge.curve.to_s)
    output = puts_curve(edge.curve, transforms)
    $file.puts output
  end
  return curve_processed
end

def puts_curve(curve, transforms)
  output = "{ \"type\": \"curve\", "
  output << "\"content\":  #{get_loop(curve.vertices, transforms)} }, "
  return output
end

def puts_face(face, transforms)
  output = "{ \"type\": \"face\", "
  output << "\"inner_loops\": #{get_innerloops(face, transforms)}, "
  output << "\"outer_loop\": #{get_loop(face.outer_loop.vertices, transforms)} }, "
  $file.puts output
end

def puts_text(text, transforms)
  if text.point.class == Geom::Point3d
    output = "{ \"type\": \"text\", "
    output << "\"text_content\": \"#{text.text}\", "
    output << "\"point\": #{get_coord(text.point, transforms)}"
    if text.vector.class == Geom::Vector3d
      output << ", \"vector\": #{get_coord(text.vector, transforms)}"
      output << ", \"length\": #{get_length(text.vector, transforms)}"
    end
    output << "}, "
    $file.puts output
  end
end

def puts_dimension_linear(dimension, transforms)
  pos = dimension.text_position
  if pos == Sketchup::DimensionLinear::TEXT_OUTSIDE_START
    output_pos = "TEXT_OUTSIDE_START"
  elsif pos == Sketchup::DimensionLinear::TEXT_CENTERED
    output_pos = "TEXT_CENTERED"
  elsif pos == Sketchup::DimensionLinear::TEXT_OUTSIDE_END
    output_pos = "TEXT_OUTSIDE_END"
  end
  output = "{ "
  output << "\"type\": \"dimension_linear\", "
  output << "\"start\": #{get_coord(dimension.start[1], transforms)}, "
  output << "\"end\": #{get_coord(dimension.end[1], transforms)}, "
  output << "\"offset_vector\": #{get_coord(dimension.offset_vector, transforms)}, "
  output << "\"start_cote\": #{get_coord(dimension.start[1]+dimension.offset_vector, transforms)}, "
  output << "\"end_cote\": #{get_coord(dimension.end[1]+dimension.offset_vector, transforms)}, "
  output << "\"offset_vector_length\": \"#{get_length(dimension.offset_vector, transforms)}\", "
  output << "\"pos\": \"#{output_pos}\"}, "
  $file.puts output
end

def puts_image(image, transforms)
  output = "{ "
  output << "\"type\": \"image\", "
  output << "\"path\": \"#{image.path}\" }, "
  $file.puts output
end

def get_innerloops(face, transforms)
  output = "["
  isFirstLoop = true
  face.loops.each do |lp|
    if !lp.outer?
      if isFirstLoop
        isFirstLoop = false
      else
        output << ","
      end
      output << get_loop(lp.vertices, transforms)
    end
  end
  output << "]"
  return output
end

def get_loop(lp, transforms)
  output = "["
  lp.each do |vertice|
    output << "{ \"curve_interior\": #{vertice.curve_interior? != nil}, "
    output << "\"point\": #{get_coord(vertice.position, transforms)} },"
  end
  output.chop!
  output << "]"
  return output
end

def get_coord(point, transforms)
  pt = transforms * point
  return "\"(#{pt.x}, #{pt.y})\""
end

def get_length(vector, transforms)
  vect = transforms * vector
  return "\"#{vect.length}\""
end

def is_grouped(entity)
  return entity.typename == "ComponentInstance" || entity.typename == "Group"
end

def get_entity_by_layer(entities, layer)
  entities.each do |entity|
    if is_grouped(entity) && entity.layer.name == layer
      return entity
    end
  end
  return nil
end

def puts_first_component(component)
  entities = component.definition.entities
  frame = get_entity_by_layer(entities,  $frame_layer)
  if frame != nil
    $file.puts "{\"name\": \"#{component.name}\", \"frame_name\": \"#{frame.name}\", \"content\": ["
    isFirst = true
    entities.each do |entity|
      if is_grouped(entity)
        if isFirst
          output = ""
          isFirst = false
        else
          output = ","
        end
        output << "{\"layer_name\": \"#{entity.layer.name}\", \"content\": ["
        $file.puts output
        puts_component(entity, frame.transformation.inverse)
        $file.puts "{\"type\": \"empty\"}]}"
      end
    end
    $file.puts "]}"
  else
    $file.puts "{\"name\": \"#{component.name}\", \"warnings\": \"no frame detected\" }"
  end
end

def puts_impressions_components(entities)
  $file.puts "["
  isFirst = true
  entities.each do |entity|
    if is_grouped(entity) && entity.layer.name == $impression_layer
      if isFirst
        isFirst = false
      else
        $file.puts ","
      end
      puts_first_component(entity)
    end
  end
  $file.puts "]"
end

def main()
  model = Sketchup.active_model
  entities = model.entities
  provider = model.options['UnitsOptions']
  provider['LengthFormat'] = Length::Decimal
  provider['LengthUnit'] = Length::Centimeter

  puts_impressions_components(entities)
end

main()
$file.close
exit_code = "Text file created at #{$file_path}. "

